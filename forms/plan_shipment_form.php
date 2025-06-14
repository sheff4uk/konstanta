<?php
include_once "../config.php";

// Сохранение/редактирование плана отгрузки
if( isset($_POST["ps_date"]) ) {
	session_start();

	// У существующего плана обновляем дату
	if( $_POST["PS_ID"] ) {
		$PS_ID = $_POST["PS_ID"];

		$query = "
			UPDATE plan__Shipment
			SET ps_date = '{$_POST["ps_date"]}'
			WHERE PS_ID = {$PS_ID}
		";
		if( !mysqli_query( $mysqli, $query ) ) $_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
	}
	// Если новый план, делаем запись в plan__Shipment
	else {
		$query = "
			INSERT INTO plan__Shipment
			SET F_ID = {$_POST["F_ID"]}
				,ps_date = '{$_POST["ps_date"]}'
		";
		if( !mysqli_query( $mysqli, $query ) ) $_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		$PS_ID = mysqli_insert_id( $mysqli );
	}

	foreach ($_POST["CWP_ID"] as $key => $value) {
		// Редактируем
		if( $_POST["cur_quantity"][$key] or $_POST["cur_quantity"][$key] == "0" ) {
			$quantity = ($_POST["quantity"][$key] == "") ? 0 : $_POST["quantity"][$key];
			$query = "
				UPDATE plan__ShipmentCWP
				SET quantity = {$quantity}
					,author = {$_SESSION['id']}
				WHERE PS_ID = {$PS_ID} AND CWP_ID = {$value}
			";
			if( !mysqli_query( $mysqli, $query ) ) $_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		elseif( $_POST["quantity"][$key] > 0 ) {
			$query = "
				INSERT INTO plan__ShipmentCWP
				SET PS_ID = {$PS_ID}
					,CWP_ID = {$value}
					,quantity = {$_POST["quantity"][$key]}
					,author = {$_SESSION['id']}
			";
			if( !mysqli_query( $mysqli, $query ) ) $_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
	}

	if( !isset($_SESSION["error"]) ) {
		$_SESSION["success"][] = "Данные успешно сохранены.";
	}
	// Вычисляем неделю для переданной даты
	$query = "
		SELECT YEARWEEK('{$_POST["ps_date"]}', 1) week
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$week = $row["week"];

	// Перенаправление в план
	exit ('<meta http-equiv="refresh" content="0; url=/plan_shipment.php?F_ID='.$_POST["F_ID"].'&week='.$week.'#'.$PS_ID.'">');
}
?>

<style>
	#plan_shipment_form table input,
	#plan_shipment_form table select {
		font-size: 1.2em;
	}
</style>

<div id='plan_shipment_form' title='Данные плана отгрузки' style='display:none;'>
	<form method='post' action="/forms/plan_shipment_form.php" onsubmit="JavaScript:this.subbut.disabled=true;this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="PS_ID">
			<input type="hidden" name="F_ID" value="<?=$_GET["F_ID"]?>">

            <h2>Планируемая дата отгрузки: <input type="date" name="ps_date" min="<?=date('Y-m-d')?>" required></h2>
			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Противовес</th>
						<th>Количество паллет</th>
						<th>Всего деталей</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<?php
					$query = "
						SELECT CWP.CWP_ID
							,IFNULL(CW.item, CWP.cwp_name) item
							,CWP.in_pallet
						FROM list__PackingPallet LPP
						JOIN CounterWeightPallet CWP ON CWP.CWP_ID = LPP.CWP_ID
						LEFT JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
						WHERE LPP.F_ID = {$_GET["F_ID"]}
							AND LPP.shipment_time IS NULL
							AND LPP.removal_time IS NULL
						GROUP BY CWP.CWP_ID
						ORDER BY CWP.CWP_ID;
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						?>
						<tr class="data_row">
							<td><b style="font-size: 1.5em;"><?=$row["item"]?></b><input type="hidden" name="CWP_ID[<?=$row["CWP_ID"]?>]" value="<?=$row["CWP_ID"]?>"></td>
							<td>
								<input type="number" name="quantity[<?=$row["CWP_ID"]?>]" in_pallet="<?=$row["in_pallet"]?>" class="quantity" min="0" max="99" tabindex="<?=(++$index)?>" style="width: 70px;">
								<input type="hidden" name="cur_quantity[<?=$row["CWP_ID"]?>]" class="cur_quantity">
							</td>
							<td><input type="number" class="amount" style="width: 70px;" readonly></td>
						</tr>
						<?php
					}
					?>
					<tr class="total">
						<td>Всего:</td>
						<td id="total_quantity"><span></span></td>
						<td></td>
					</tr>
				</tbody>
			</table>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Записать' style='float: right;'>
		</div>
	</form>
</div>

<script>
	$(function() {
		// Кнопка добавления
		$('.add_ps').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			var ps_id = $(this).attr("ps_id"),
				ps_date = $(this).attr("ps_date"),
				priority = $(this).attr("priority"),
				ps_data = <?= json_encode($ps_data); ?>;

			$('#plan_shipment_form input[name="PS_ID"]').val(ps_id);
			$('#plan_shipment_form input[name="ps_date"]').val(ps_date);

			// Очищаем форму
			$('#plan_shipment_form .data_row input.quantity').val('');
			$('#plan_shipment_form .data_row input.amount').val('');
			$('#plan_shipment_form .data_row input.cur_quantity').val('');
			$('#plan_shipment_form .total span').html('');

			if( ps_id ) {
				for (let sub_ps_data of ps_data[ps_id]) {
					$('#plan_shipment_form input[name="quantity[' + sub_ps_data['CWP_ID'] + ']"]').val(sub_ps_data['quantity']).change();
					$('#plan_shipment_form input[name="cur_quantity[' + sub_ps_data['CWP_ID'] + ']"]').val(sub_ps_data['quantity']);
				}
			}

			$('#plan_shipment_form').dialog({
				resizable: false,
				width: 600,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// При изменении кол-ва паллет пересчитываем тотал и число деталей справа
		$('#plan_shipment_form input.quantity').change(function() {
			var quantity = $(this).val(),
				total_quantity = 0,
				in_pallet = $(this).attr('in_pallet');

			$(this).parents('tr').find('.amount').val(quantity * in_pallet);

			// Вычисляем тотал
			$('.data_row').each(function(){
				total_quantity += Number($(this).children().children('input.quantity').val());
			});

			$('#total_quantity span').html(total_quantity);
		});
	});
</script>
