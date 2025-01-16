<?php
include "config.php";

//Редактирование времени выдержки
if( isset($_POST["CB_ID"]) ) {
    session_start();
    $holding_time = ($_POST["holding_time"] > 0) ? $_POST["holding_time"] : "NULL";
	$query = "
		UPDATE ClientBrand
		SET holding_time = {$holding_time}
		WHERE CB_ID = {$_POST["CB_ID"]}
	";
	if( !mysqli_query( $mysqli, $query ) ) {
		$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
	}
	if( !isset($_SESSION["error"]) ) {
		$_SESSION["success"][] = "Запись успешно отредактирована.";
	}

	// Перенаправление
	exit ('<meta http-equiv="refresh" content="0; url=#CB_'.$_POST["CB_ID"].'">');
}

//Редактирование статуса сбора данных с весов
if( isset($_POST["WT_ID"]) ) {
    session_start();
    $act = ($_POST["act"] > 0) ? "1" : "0";
	$query = "
		UPDATE WeighingTerminal
		SET act = {$act}
		WHERE WT_ID = {$_POST["WT_ID"]}
	";
	if( !mysqli_query( $mysqli, $query ) ) {
		$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
	}
	if( !isset($_SESSION["error"]) ) {
		$_SESSION["success"][] = "Запись успешно отредактирована.";
	}

	// Перенаправление
	exit ('<meta http-equiv="refresh" content="0; url=#WT_'.$_POST["WT_ID"].'">');
}

$title = 'Настройки';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('settings', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}
?>

<!--Таблица с временем выдержки противовесов-->
<h1>Время выдержки противовесов:</h1>
<table class="main_table">
	<thead>
		<tr>
			<th>Заказчик</th>
			<th>Время выдержки, ч.</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?php
$query = "
	SELECT CB.CB_ID
		,CB.brand
		,CB.holding_time
        ,IFNULL(CB.holding_time, 'Не учитывается') holding_time_friendly
	FROM ClientBrand CB
	ORDER BY CB.CB_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr id="CB_<?=$row["CB_ID"]?>">
		<td><?=$row["brand"]?></td>
		<td><?=$row["holding_time_friendly"]?></td>
		<td><a href="#" class="holding_time_edit" CB_ID="<?=$row["CB_ID"]?>" brand="<?=$row["brand"]?>" holding_time="<?=$row["holding_time"]?>" title="Редактировать"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?php
}
?>
	</tbody>
</table>
<!--Конец таблицы с временем выдержки противовесов-->

<!--Форма редактирования-->
<style>
	#holding_time_edit_form table td {
		font-size: 1.5em;
	}
</style>

<div id='holding_time_edit_form' title='Редактирование времени выдержки' style='display:none;'>
	<form method='post' onsubmit="this.subbut.disabled=true;this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="CB_ID">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Заказчик</th>
						<th>Время выдержки, ч.</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td id="brand"></td>
						<td><input type="number" name="holding_time" min="1" max="300" style="width: 70px;"></td>
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
<!--Конец формы-->
<script>
	$(function() {
		$('.holding_time_edit').click( function() {
			var CB_ID = $(this).attr("CB_ID"),
				brand = $(this).attr("brand"),
				holding_time = $(this).attr("holding_time");

			$('#holding_time_edit_form input[name="CB_ID"]').val(CB_ID);
			$('#holding_time_edit_form input[name="holding_time"]').val(holding_time);
			$('#holding_time_edit_form #brand').text(brand);

			$('#holding_time_edit_form').dialog({
				resizable: false,
				width: 600,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

	});
</script>

<!--Таблица с весовыми терминалами-->
<h1>Весовые терминалы:</h1>
<table class="main_table">
	<thead>
		<tr>
			<th>Участок</th>
			<th>Пост</th>
			<th>Номер терминала</th>
			<th>Номер последней регистрации</th>
			<th>Сбор данных</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?php
$query = "
	SELECT WT.WT_ID
		,WT.post
		,IF(WT.act = 1, '<font color=\"green\">Включен</font>', '<font color=\"red\">Отключен</font>') status
		,WT.act
		,F.f_name
		,last_transaction
	FROM WeighingTerminal WT
		JOIN factory F ON F.F_ID = WT.F_ID
	WHERE WT.type = 2
	ORDER BY WT.F_ID, WT.post
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr id="WT_<?=$row["WT_ID"]?>">
		<td><?=$row["f_name"]?></td>
		<td><?=$row["post"]?></td>
		<td><?=$row["WT_ID"]?></td>
		<td><?=$row["last_transaction"]?></td>
		<td><?=$row["status"]?></td>
		<td><a href="#" class="weighting_terminal_edit" WT_ID="<?=$row["WT_ID"]?>" act="<?=$row["act"]?>" f_name="<?=$row["f_name"]?>" post="<?=$row["post"]?>" WT_ID="<?=$row["WT_ID"]?>" title="Редактировать"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?php
}
?>
	</tbody>
</table>
<!--Конец таблицы с весовыми терминалами-->
<!--Форма редактирования-->
<style>
	#weighting_terminal_edit_form table td {
		font-size: 1.5em;
	}

	input[type="checkbox"].wt_act:checked + label span:before {
		content: "Включен";
	}

	input[type="checkbox"].wt_act + label span:before{
		content: "Отключен";
	}

</style>

<div id='weighting_terminal_edit_form' title='Редактирование статуса весов' style='display:none;'>
	<form method='post' onsubmit="this.subbut.disabled=true;this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="WT_ID">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Участок</th>
						<th>Пост</th>
						<th>Номер терминала</th>
						<th>Сбор данных</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td id="f_name"></td>
						<td id="post"></td>
						<td id="WT_ID"></td>
						<td>
							<input type="checkbox" name="act" class="wt_act" val="1" id="wt_act">
							<label for="wt_act"></label>
						</td>
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
<!--Конец формы-->
<script>
	$(function() {
		$('.weighting_terminal_edit').click( function() {
			var f_name = $(this).attr("f_name"),
				post = $(this).attr("post"),
				WT_ID = $(this).attr("WT_ID"),
				act = $(this).attr("act");

			$('#weighting_terminal_edit_form input[name="WT_ID"]').val(WT_ID);
			$('#weighting_terminal_edit_form #f_name').text(f_name);
			$('#weighting_terminal_edit_form #post').text(post);
			$('#weighting_terminal_edit_form #WT_ID').text(WT_ID);
			$('#weighting_terminal_edit_form #wt_act').prop('checked',act);
			$('.wt_act').button();

			$('#weighting_terminal_edit_form').dialog({
				resizable: false,
				width: 800,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

	});
</script>

<?php
include "footer.php";
?>
