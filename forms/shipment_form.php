<?php
include_once "../config.php";

// Сохранение/редактирование отгрузки
if( isset($_POST["CWP_ID"]) ) {
	session_start();
	$ls_date = $_POST["ls_date"];
	$CWP_ID = $_POST["CWP_ID"];
	$pallets = $_POST["pallets"];
	$in_pallet = $_POST["in_pallet"];
	$PN_ID = $_POST["PN_ID"];

	if( $_POST["LS_ID"] ) { // Редактируем
		$query = "
			UPDATE list__Shipment
			SET ls_date = '{$ls_date}'
				,CWP_ID = {$CWP_ID}
				,pallets = {$pallets}
				,in_pallet = {$in_pallet}
				,PN_ID = {$PN_ID}
			WHERE LS_ID = {$_POST["LS_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$LS_ID = $_POST["LS_ID"];
	}
	else { // Добавляем
		$query = "
			INSERT INTO list__Shipment
			SET ls_date = '{$ls_date}'
				,CWP_ID = {$CWP_ID}
				,pallets = {$pallets}
				,in_pallet = {$in_pallet}
				,PN_ID = {$PN_ID}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$LS_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( !isset($_SESSION["error"]) ) {
		$_SESSION["success"][] = $add ? "Новая запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Получаем неделю
	$query = "SELECT YEARWEEK('{$ls_date}', 1) week";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$week = $row["week"];

	// Перенаправление в журнал
	if( $add ) {
		exit ('<meta http-equiv="refresh" content="0; url=/shipment.php?week='.$week.'&ls_date='.$ls_date.'&add#'.$LS_ID.'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/shipment.php?week='.$week.'#'.$LS_ID.'">');
	}
}
?>

<style>
	#shipment_form table input,
	#shipment_form table select {
		font-size: 1.2em;
	}
</style>

<div id='shipment_form' title='Данные отгрузки' style='display:none;'>
	<form method='post' action="/forms/shipment_form.php" onsubmit="JavaScript:this.subbut.disabled=true;this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="LS_ID">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Дата</th>
						<th>Комплект противовесов</th>
						<th>Поддон</th>
						<th>Паллетов</th>
						<th>Деталей в паллете</th>
						<th>Всего деталей</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><input type="date" name="ls_date" required></td>
						<td>
							<select name="CWP_ID" style="width: 150px;" required>
								<option value=""></option>
								<?php
								$query = "
									SELECT CWP.CWP_ID
										,IFNULL(CW.item, CWP.cwp_name) item
										,CWP.in_pallet
									FROM CounterWeightPallet CWP
									LEFT JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
									ORDER BY CWP.CWP_ID
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["CWP_ID"]}' in_pallet='{$row["in_pallet"]}'>{$row["item"]} ({$row["in_pallet"]}шт)</option>";
								}
								?>
							</select>
						</td>
						<td>
							<select name="PN_ID" style="width: 150px;" required>
								<option value=""></option>
								<?php
								$query = "
									SELECT PN.PN_ID
										,PN.pallet_name
									FROM pallet__Name PN
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								while( $row = mysqli_fetch_array($res) ) {
									echo "<option value='{$row["PN_ID"]}'>{$row["pallet_name"]}</option>";
								}
								?>
							</select>
						</td>
						<td><input type="number" name="pallets" min="0" max="132" style="width: 70px;" required></td>
						<td><input type="number" name="in_pallet" min="0" max="100" style="width: 70px;" required></td>
						<td><input type="number" name="amount" style="width: 70px;" readonly></td>
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

			var LS_ID = $(this).attr("LS_ID"),
				ls_date = $(this).attr("ls_date");

			// В случае редактирования заполняем форму
			if( LS_ID ) {
				// Данные аяксом
				$.ajax({
					url: "/ajax/shipment_json.php?LS_ID=" + LS_ID,
					success: function(msg) { ps_data = msg; },
					dataType: "json",
					async: false
				});

				$('#shipment_form select[name="CWP_ID"]').val(ps_data['CWP_ID']);
				$('#shipment_form select[name="PN_ID"]').val(ps_data['PN_ID']);
				$('#shipment_form input[name="pallets"]').val(ps_data['pallets']);
				$('#shipment_form input[name="in_pallet"]').val(ps_data['in_pallet']);
				$('#shipment_form input[name="amount"]').val(ps_data['amount']);
				$('#shipment_form input[name="LS_ID"]').val(LS_ID);
				$('#shipment_form input[name="ls_date"]').val(ps_data['ls_date']);
			}
			// Иначе очищаем форму
			else {
				$('#shipment_form input[name="LS_ID"]').val('');
				$('#shipment_form table input').val('');
				$('#shipment_form table select').val('');
				$('#shipment_form table input[name="ls_date"]').val(ls_date);
			}

			$('#shipment_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// При изменении противовеса обновляем число деталей в поддоне
		$('#shipment_form select[name="CWP_ID"]').change(function() {
			var in_pallet = $('#shipment_form select[name="CWP_ID"] option:selected').attr('in_pallet');

			$('#shipment_form input[name="in_pallet"]').val(in_pallet).change();
		});

		// При изменении в паллете или паллетов пересчитываем детали
		$('#shipment_form input[name="in_pallet"], #shipment_form input[name="pallets"]').change(function() {
			var in_pallet = $('#shipment_form input[name="in_pallet"]').val(),
				pallets = $('#shipment_form input[name="pallets"]').val();

			$('#shipment_form input[name="amount"]').val(pallets * in_pallet);
		});
	});
</script>
