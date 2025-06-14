<?php
include_once "../config.php";
include_once "../checkrights.php";

// Сохранение/редактирование
if( isset($_POST["F_ID"]) ) {
	session_start();
	$F_ID = $_POST["F_ID"];
	$month = $_POST["month"];
	$user_type = $_POST["user_type"];

	if( isset($_POST["TS_ID"]) ) {
		$TS_ID = $_POST["TS_ID"];
		// Узнаем ts_date, USD_ID
		$query = "
			SELECT TS.ts_date
				,USR_ID
			FROM Timesheet TS
			WHERE TS.TS_ID = {$TS_ID}
		";
		$res = mysqli_query( $mysqli, $query );
		$row = mysqli_fetch_array($res);
		$ts_date = $row["ts_date"];
		$USR_ID = $row["USR_ID"];
	}
	else {
		if( $_POST["tr_time1"] or $_POST["tr_time0"] or $_POST["status"] != '' or $_POST["payout"] ) {
			// Делаем запись в табеле
			$ts_date = $_POST["ts_date"];
			$USR_ID = $_POST["usr_id"];
			$query = "
				INSERT INTO Timesheet
				SET ts_date = '{$ts_date}'
					,USR_ID = {$USR_ID}
					,F_ID = {$F_ID}
			";
			mysqli_query( $mysqli, $query );
			$TS_ID = mysqli_insert_id( $mysqli );
		}
	}

	// Редактирование тарифа смены
	foreach ($_POST["tss_tariff"] as $key => $value) {
		$query = "
			UPDATE TimesheetShift
			SET tss_tariff = {$value}
				,tss_type = {$_POST["tss_type"][$key]}
				,tss_author = {$_SESSION['id']}
			WHERE TSS_ID = {$key}
		";
		mysqli_query( $mysqli, $query );
	}

	// Проверка можно ли редактировать регистрации
	$query = "
		SELECT IF(TIMESTAMPDIFF(DAY, TS.ts_date, CURDATE()) <= 40 AND TIMESTAMPDIFF(HOUR, TS.ts_date, NOW()) >= 32, 1, 0) editable
		FROM Timesheet TS
		WHERE TS.TS_ID = {$TS_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$editable = $row["editable"];
	if( $editable ) {
		//////////////////////////////////////////////////
		// Проверяем валидность добавленных регистраций //
		//////////////////////////////////////////////////
		if( $_POST["tr_time1"] ) {
			// Преобразуем время в минуты
			$date = substr($_POST["tr_time1"], 0, 10);
			$time = substr($_POST["tr_time1"], -5);
			$query = "
				SELECT HOUR('{$time}') * 60 + MINUTE('{$time}') + TIMESTAMPDIFF(MINUTE, '{$ts_date}', '{$date}') tr_minute
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);
			$tr_minute1 = $row["tr_minute"];
		}
		if( $_POST["tr_time0"] ) {
			// Преобразуем время в минуты
			$date = substr($_POST["tr_time0"], 0, 10);
			$time = substr($_POST["tr_time0"], -5);
			$query = "
				SELECT HOUR('{$time}') * 60 + MINUTE('{$time}') + TIMESTAMPDIFF(MINUTE, '{$ts_date}', '{$date}') tr_minute
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);
			$tr_minute0 = $row["tr_minute"];
		}

		// Проверка регистрации открытия
		if( isset($tr_minute1) ) {
			// По времени регистрации вычисляем номер смены
			$query = "
				SELECT WS.shift_num
				FROM WorkingShift WS
				WHERE WS.F_ID = {$F_ID}
					AND {$tr_minute1} + 120 BETWEEN WS.shift_start AND WS.shift_end
					AND '{$ts_date}' BETWEEN WS.valid_from AND IFNULL(WS.valid_to, CURDATE())
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			if( $row = mysqli_fetch_array($res) ) {
				$shift_num = $row["shift_num"];

				// Проверка регистрации закрытия
				if( isset($tr_minute0) ) {
					// Время закрытия должно быть больше открытия
					if( $tr_minute0 >= $tr_minute1 ) {
						// Обе регистрации должны быть в одной смене
						$query = "
							SELECT 1
							FROM WorkingShift WS
							WHERE WS.F_ID = {$F_ID}
								AND {$tr_minute0} BETWEEN WS.shift_start - 120 AND WS.shift_end + 120
								AND '{$ts_date}' BETWEEN WS.valid_from AND IFNULL(WS.valid_to, CURDATE())
								AND WS.shift_num = {$shift_num}
						";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						if( !$row = mysqli_fetch_array($res) ) {
							$_SESSION["error"][] = 'Обе регистрации должны принадлежать одной смене. Регистрации не сохранены.';
							unset($tr_minute1);
							unset($tr_minute0);
						}
					}
					else {
						$_SESSION["error"][] = 'Закрытие смены должно следовать после открытия. Регистрации не сохранены.';
						unset($tr_minute1);
						unset($tr_minute0);
					}
				}
				else {
					// Ищем подходящую регистрацию закрытия из существующих
					$query = "
						SELECT TR.TR_ID
						FROM TimeReg TR
						JOIN TimesheetShift TSS ON TSS.TSS_ID = TR.TSS_ID
							AND TSS.shift_num = {$shift_num}
						JOIN Timesheet TS ON TS.TS_ID = TSS.TS_ID
							AND TS.TS_ID = {$TS_ID}
						WHERE TR.prefix = 0
							AND TR.del_time IS NULL
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					if( !$row = mysqli_fetch_array($res) ) {
						$_SESSION["error"][] = 'Для нового открытия смены не найдено закрытие. Регистрация не сохранена.';
						unset($tr_minute1);
					}
				}
			}
			else {
				$_SESSION["error"][] = 'Открытие смены указано в нерабочее время. Регистрация не сохранена.';
				unset($tr_minute1);
			}
		}
		// Проверка регистрации закрытия
		elseif( isset($tr_minute0) ) {
			// Узнаем номер смены, которую хотим закрыть
			$query = "
				SELECT ({$tr_minute0} - TR.tr_minute) `duration`
					,TSS.shift_num
				FROM TimeReg TR
				JOIN TimesheetShift TSS ON TSS.TSS_ID = TR.TSS_ID
				JOIN Timesheet TS ON TS.TS_ID = TSS.TS_ID
					AND TS.TS_ID = {$TS_ID}
				WHERE TR.prefix = 1
					AND TR.del_time IS NULL
					AND TR.tr_minute <= {$tr_minute0}
				ORDER BY `duration` ASC
				LIMIT 1
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			if( $row = mysqli_fetch_array($res) ) {
				$duration = $row["duration"];
				$shift_num = $row["shift_num"];

				// Проверка на соответствие времени закрытия диапазону смены
				$query = "
					SELECT 1
					FROM WorkingShift WS
					WHERE WS.F_ID = {$F_ID}
						AND {$tr_minute0} BETWEEN WS.shift_start - 120 AND WS.shift_end + 120
						AND '{$ts_date}' BETWEEN WS.valid_from AND IFNULL(WS.valid_to, CURDATE())
						AND WS.shift_num = {$shift_num}
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				if( !$row = mysqli_fetch_array($res) ) {
					$_SESSION["error"][] = 'Время закрытия выходит за границы смены. Регистрация не сохранена.';
					unset($tr_minute0);
				}
			}
			else {
				$_SESSION["error"][] = 'Для нового закрытия смены не найдено открытие. Регистрация не сохранена.';
				unset($tr_minute0);
			}
		}
		////////////////////////////////////

		/////////////////////////////////////
		// Добавление валидных регистраций //
		/////////////////////////////////////
		if( isset($tr_minute1) or isset($tr_minute0) ) {
			// Добавляем запись номера смены
			$query = "
				INSERT INTO TimesheetShift
				SET TS_ID = {$TS_ID}
					,shift_num = {$shift_num}
				ON DUPLICATE KEY UPDATE
					shift_num = shift_num
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			# Узнаем TSS_ID
			$query = "
				SELECT TSS_ID
				FROM TimesheetShift
				WHERE TS_ID = {$TS_ID}
					AND shift_num = {$shift_num}
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);
			$TSS_ID = $row["TSS_ID"];

			if( isset($tr_minute1) ) {
				// Удаляем конфликтующую регистрацию
				$query = "
					UPDATE TimeReg
					SET del_time = NOW()
						,del_author = {$_SESSION['id']}
					WHERE TSS_ID = {$TSS_ID}
						AND prefix = 1
						AND del_time IS NULL
				";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

				// Добавляем регистрацию работника
				$query = "
					INSERT INTO TimeReg
					SET TSS_ID = {$TSS_ID}
						,prefix = 1
						,tr_minute = {$tr_minute1}
						,add_time = NOW()
						,add_author = {$_SESSION['id']}
				";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			}

			if( isset($tr_minute0) ) {
				// Удаляем конфликтующую регистрацию
				$query = "
					UPDATE TimeReg
					SET del_time = NOW()
						,del_author = {$_SESSION['id']}
					WHERE TSS_ID = {$TSS_ID}
						AND prefix = 0
						AND del_time IS NULL
				";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

				// Добавляем регистрацию работника
				$query = "
					INSERT INTO TimeReg
					SET TSS_ID = {$TSS_ID}
						,prefix = 0
						,tr_minute = {$tr_minute0}
						,add_time = NOW()
						,add_author = {$_SESSION['id']}
				";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			}
		}
		//////////////////////////////
	}

	////////////////////////////////////
	// Помечаем удаленные регистрации //
	////////////////////////////////////
	foreach ($_POST["del_reg"] as $key => $value) {
		$query = "
			UPDATE TimeReg
			SET del_time = NOW()
				,del_author = {$_SESSION['id']}
			WHERE TR_ID = {$value}
		";
		mysqli_query( $mysqli, $query );
	}
	/////////////////////////////////////

	//////////////////////
	// Обновляем статус //
	//////////////////////
	if( $TS_ID ) {
		$status = ($_POST["status"] != '' ? $_POST["status"] : "NULL");
		$query = "
			UPDATE Timesheet
			SET status = {$status}
				,fine = IF({$status} = 5, 5000, NULL)
			WHERE TS_ID = {$TS_ID}
		";
		mysqli_query( $mysqli, $query );
	}
	///////////////////////////////////

	////////////////////
	// Обновляем займ //
	////////////////////
	if( $TS_ID ) {
		$payout = ($_POST["payout"] != '' ? $_POST["payout"] : "NULL");
		$comment = convert_str($_POST["comment"]);
		$comment = mysqli_real_escape_string($mysqli, $comment);
		$query = "
			UPDATE Timesheet
			SET payout = {$payout}
				,comment = '{$comment}'
			WHERE TS_ID = {$TS_ID}
		";
		mysqli_query( $mysqli, $query );
	}
	///////////////////////////////////

	if( !isset($_SESSION["error"]) ) {
		$_SESSION["success"][] = "Все изменения сохранены.";
	}

	// Перенаправление в табель
	exit ('<meta http-equiv="refresh" content="0; url=/timesheet.php?F_ID='.$F_ID.'&month='.$month.'&user_type='.$user_type.'#'.$TS_ID.'">');
}
?>

<style>
	.wr_photo {
		transition: all 0.3s ease-in-out;
		display: flex;
		width: 160px;
		height: 40px;
		background-color: #fdce46;
		background-size: cover;
		border-radius: 5px;
		margin: 5px auto;
		overflow: hidden;
		position: relative;
		box-shadow: 5px 5px 8px rgb(0 0 0 / 60%);
	}
	.wr_photo:hover {
		height: 120px !important;
	}
</style>

<div id='timesheet_form' style='display:none;'>
	<form method='post' action="/forms/timesheet_form.php" onsubmit="JavaScript:this.subbut.disabled=true;this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="F_ID" value="<?=$F_ID?>">
			<input type="hidden" name="month" value="<?=$_GET["month"]?>">
			<input type="hidden" name="user_type" value="<?=$_GET["user_type"]?>">

			<div id="hide"><!--Формируется скриптом--></div>
			<div id="summary"><!--Формируется скриптом--></div>


			<div style="display: flex; justify-content: space-between; font-size: 1.3em;">
				<fieldset>
					<legend>Статус:</legend>
					<div style="display: flex; margin: .5em 0;">
						<select name="status" style="width: 150px;">
							<option value=""></option>
							<option value="0">Прочерк</option>
							<option value="1">Отпуск</option>
							<option value="2">Уволен</option>
							<option value="3">Больничный</option>
							<option value="4">Выходной</option>
							<option value="5">Прогул</option>
							<option value="6">Командировка</option>
						</select>
					</div>
				</fieldset>

				<fieldset>
					<legend>Займ:</legend>
					<div style="display: flex; justify-content: space-between;">
						<div style="display: flex; margin: .5em 0;">
							<input type="number" name="payout" min="0" placeholder="Сумма" style="width: 100px;">
						</div>
						<div style="display: flex; margin: .5em 0;">
							<input type="text" name="comment" placeholder="Комментарий"  style="width: 400px;" autocomplete="off">
						</div>
					</div>
				</fieldset>
			</div>

		<span style="color: #911;">Если временной интервал между регистрациями входа и выхода составит меньше 30 минут, то начисление не произойдет при любом типе тарифа!</span>
			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Смена</th>
						<th>Время регистрации</th>
						<th>Время добавления</th>
						<th>Время отмены</th>
					</tr>
				</thead>
				<tbody id="timereg" style="text-align: center;">
					<!--Формируется скриптом-->
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
		$('.tscell').on('click', function(){
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			$('#timesheet_form select[name=status]').val('');
			$('#timesheet_form input[name=payout]').val('');
			$('#timesheet_form input[name=comment]').val('');

			var ts_id = $(this).attr('ts_id'),
				date_format = $(this).attr('date_format'),
				date = $(this).attr('date'),
				tomorrow = $(this).attr('tomorrow'),
				editable = $(this).attr('editable'),
				usr_name = $(this).attr('usr_name'),
				html = '',
				html_hide = '',
				html_summary = '';

			if( ts_id > 0 ) {
				// Формируем скрытые поля
				html_hide = html_hide + "<input type='hidden' name='TS_ID' value='"+ts_id+"'>";

				var arr_reg = TimeReg[ts_id];
				var arr_shift = TimesheetShift[ts_id];
				var status = $(this).attr('status'),
					photo = $(this).attr('photo'),
					payout = $(this).attr('payout'),
					comment = $(this).attr('comment');

				$('#timesheet_form select[name=status]').val(status);
				$('#timesheet_form input[name=payout]').val(payout);
				$('#timesheet_form input[name=comment]').val(comment);

				var html_photo = '';
				if( photo ) {
					html_photo = "<img src='/time_tracking/upload/"+photo+"' style='width: 100%; border-radius: 5px;'>";
				}

				if( arr_shift ) {
					var shift_num = '',
						tariff_type = '',
						duration = '',
						pay = '';

					$.each(arr_shift, function(key, val){
						shift_num = shift_num + val["shift_num"] + "<br>";
						tariff_type = tariff_type
							+ "<input type='number' name='tss_tariff[" + val["TSS_ID"] + "]' min='0' style='width: 70px;' value='" + val["tss_tariff"] + "' required>"
							+ "<select name='tss_type[" + val["TSS_ID"] + "]' style='width: 150px;' required>"
								+ "<option value=''></option>"
								+ "<option value='1' " + (val["tss_type"] == 1 ? 'selected' : '') + ">Смена</option>"
								+ "<option value='2' " + (val["tss_type"] == 2 ? 'selected' : '') + ">Час-смена (от начала смены до регистрации выхода)</option>"
								+ "<option value='3' " + (val["tss_type"] == 3 ? 'selected' : '') + ">Час-вход (от регистрации входа до регистрации выхода)</option>"
								+ "<option value='4' " + (val["tss_type"] == 4 ? 'selected' : '') + ">Месяц (начисление происходит только в рабочие дни)</option>"
							+ "</select>"
							+ (val["last_edit"] ? val["tss_author"] + "<i class='fas fa-clock fa-lg' title='" + val["last_edit"] + "'></i>" : '')
							+ "<br>";
						duration = duration + val["duration_hm"] + "<br>";
						pay = pay + val["pay"] + "<br>";

					});

					html_summary = html_summary
						+ "<table style='width: 100%; table-layout: fixed; margin-bottom: 20px; border: 5px solid #999;'>"
							+ "<thead><tr>"
								+ "<th></th>"
								+ "<th>Смена</th>"
								+ "<th colspan='3'>Тариф</th>"
								+"<th>Длительность</th>"
								+"<th>Расчет</th>"
							+"</tr></thead>"
							+"<tbody style='text-align: center; font-size: 1.3em;'><tr>"
								+ "<td>" + html_photo + "</td>"
								+ "<td>" + shift_num + "</td>"
								+ "<td colspan='3' style='font-size: 14px; text-align: left;'>" + tariff_type + "</td>"
								+ "<td>" + duration + "</td>"
								+ "<td>" + pay + "</td>"
							+ "</tr></tbody>"
						+ "</table>";
				}

				if( arr_reg ) {
					$.each(arr_reg, function(key, val){
						var del_style = '',
							photo_style = '';

						if( val["del_time"] != '' ) {
							del_style = 'background: #0006;';
						}

						if( val["tr_photo"] ) {
							photo_style = "background-image: url(/time_tracking/upload/"+val["tr_photo"]+");";
						}

						html = html + "<tr>";
						html = html + "<td style='"+del_style+"'><span style='font-size: 2em;'>"+val["shift_num"]+"</span></td>";
						html = html + "<td style='"+del_style+"'><div style='display: flex;'><div class='wr_photo' style='"+photo_style+"'><span style='filter: drop-shadow(0px 0px 2px #000); color: #fff; font-size: 1.5em; margin: 5px;'>"+val["tr_time"]+" "+val["prefix"]+"</span></div></div></td>";
						html = html + "<td style='"+del_style+"'><div style='height: 50px; display: flex; align-items: center; justify-content: space-evenly;'><div>"+val["add_author"]+"</div><div>"+val["add_time"]+"</div></div></td>";
						if( val["del_time"] != '' ) {
							html = html + "<td style='"+del_style+" color: #911;'><div style='height: 50px; display: flex; align-items: center; justify-content: space-evenly;'><div>"+val["del_author"]+"</div><div>"+val["del_time"]+"</div></div></td>";
						}
						else {
							html = html + "<td style='"+del_style+"'><div style='height: 50px; display: flex; align-items: center; justify-content: space-evenly;'></div></td>";
						}
						html = html + "</tr>";
					});
				}
			}
			else {
				var ts_date = $(this).attr('ts_date'),
					usr_id = $(this).attr('usr_id');

				// Формируем скрытые поля
				html_hide = html_hide + "<input type='hidden' name='ts_date' value='"+ts_date+"'>";
				html_hide = html_hide + "<input type='hidden' name='usr_id' value='"+usr_id+"'>";
			}

			html = html + "<tr><td colspan='2'>Открытие <input type='datetime-local' name='tr_time1' min='"+date+"T00:00' max='"+tomorrow+"T08:00' def='"+date+"T00:00' "+(editable == 1 ? "" : "disabled")+" style='margin: 10px; font-size: 1.2em;'></td><td colspan='2' rowspan='2'>Редактирование регистраций возможно в течении 40 дней и только за прошлые дни.</td></tr>";
			html = html + "<tr><td colspan='2'>Закрытие <input type='datetime-local' name='tr_time0' min='"+date+"T00:00' max='"+tomorrow+"T08:00' def='"+tomorrow+"T08:00' "+(editable == 1 ? "" : "disabled")+" style='margin: 10px; font-size: 1.2em;'></td></tr>";

			$('#timesheet_form #hide').html(html_hide);
			$('#timesheet_form #summary').html(html_summary);
			$('#timesheet_form #timereg').html(html);

			$('#timesheet_form').dialog({
				title: date_format + ' | ' + usr_name,
				resizable: false,
				width: 800,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		$('#timesheet_form #timereg').on('change', 'input[name=tr_time1]', function(event) {
			var def = $(this).attr('def');
				border = ($(this).val() != '') ? $(this).val() : def;
			$('#timereg input[name=tr_time0]').attr('min', border);
		});

		$('#timesheet_form #timereg').on('change', 'input[name=tr_time0]', function(event) {
			var def = $(this).attr('def');
				border = ($(this).val() != '') ? $(this).val() : def;
			$('#timereg input[name=tr_time1]').attr('max', border);
		});

		$('#timesheet_form tbody').on('change', '.del_reg', function(event) {
			if( $(this).is(":checked") ) {
				$(this).parents('td').css('background', '#f005');
			}
			else {
				$(this).parents('td').css('background', 'none');
			}
		});
	});
</script>
