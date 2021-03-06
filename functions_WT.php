<?
// Двоичная строка в массив отдельных байт
function byteStr2byteArray($s) {
	return array_slice(unpack("C*", "\0".$s), 1);
}

function crc16($buf) {
	$crc = 0;
	$tab = 5;
	for ($k = $tab; $k < count($buf); $k++) {
		$accumulator = 0;

		$temp = (($crc >> 8) << 8);

		for ($bits = 0; $bits < 8; $bits++) {
			if (($temp ^ $accumulator) & 0x8000) {
				$accumulator = (($accumulator << 1) ^ 0x1021);
				$accumulator = $accumulator & 0xFFFF;
			}
			else {
				$accumulator <<= 1;
				$accumulator = $accumulator & 0xFFFF;
			}
			$temp <<= 1;
			$temp = $temp & 0xFFFF;
		}
		$crc <<= 8;
		$crc = $crc & 0xFFFF;
		$crc = ($accumulator ^ $crc ^ ($buf[$k] & 0xff));
	}
	// Меняем местами байты и преобразуем в шестнадцатеричную строку
	return sprintf("%02x%02x", ($crc & 0xFF), (($crc >> 8) & 0xFF));
}

// Функция читает регистрации с терминалов на конвейере
function read_transaction_LW($ID, $curnum, $socket, $mysqli) {
	$hexID = sprintf("%02x%02x%02x%02x", ($ID & 0xFF), (($ID >> 8) & 0xFF), (($ID >> 16) & 0xFF), (($ID >> 24) & 0xFF));
	$hexcurnum = sprintf("%02x%02x", ($curnum & 0xFF), (($curnum >> 8) & 0xFF));
	$in = "\xF8\x55\xCE\x0C\x00\x92\x03\x00\x00".hex2bin($hexcurnum).hex2bin($hexID)."\x00\x00";
	$crc = crc16(byteStr2byteArray($in));
	$in .= hex2bin($crc);

	socket_write($socket, $in);

	//Заголовок
	$result = socket_read($socket, 3);

	//Длина тела сообщения
	$length = socket_read($socket, 2);
	$result .= $length;
	$length = hexdec(bin2hex($length));
	$length = (($length & 0xFF) << 8) + (($length >> 8) & 0xFF);

	// Тело сообщения
	$result .= socket_read($socket, $length);

	//Читаем CRC
	$crc = socket_read($socket, 2);

	//Ответ в массив
	$data = byteStr2byteArray($result);

	//Сравниваем CRC
	if( crc16($data) == bin2hex($crc) ) {

		//Если ответ 0x52 CMD_TCP_ACK_TRANSACTION
		if( $data[5] == 0x52 ) {

			//Число частей в файле
			$nums = $data[7] + ($data[8] << 8);

			//Номер текущей части
			$curnum = $data[9] + ($data[10] << 8);

			//Длина записи
			$curlen = $data[11] + ($data[12] << 8);

			for( $i=13; $i < $curlen; $i=$i+104) {
				// Этикетирование
				if( $data[$i+10] == 1 ) {
					//Идентификатор
					$nextID = $data[$i] + ($data[$i+1] << 8) + ($data[$i+2] << 16) + ($data[$i+3] << 24);
					//Номер терминала
					$deviceID = $data[$i+6] + ($data[$i+7] << 8) + ($data[$i+8] << 16) + ($data[$i+9] << 24);
					//Дата/время совершения регистрации
					$transactionDate = sprintf("20%02d-%02d-%02d %02d:%02d:%02d", $data[$i+11], $data[$i+12], $data[$i+13], $data[$i+14], $data[$i+15], $data[$i+16]);
					//Масса нетто
					$netWeight = $data[$i+19] + ($data[$i+20] << 8) + ($data[$i+21] << 16) + ($data[$i+22] << 24);
					//Если масса отрицательная
					if( ($data[$i+22] >> 7) == 1 ) {
						$netWeight = ((-1 >> 32) << 32) + $netWeight;
					}
					//ID товара
					$goodsID = $data[$i+37] + ($data[$i+38] << 8) + ($data[$i+39] << 16) + ($data[$i+40] << 24);
					$goodsID = ($goodsID == 8 ? 1 : $goodsID);
					//Номер партии
					$ReceiptNumber = $data[$i+76] + ($data[$i+77] << 8) + ($data[$i+78] << 16) + ($data[$i+79] << 24);

					// Игнорируем недопустимый вес
					if( abs($netWeight) >= 7000 and abs($netWeight) <= 14000 ) {
						// Отмена регистрации
						if( $netWeight < 0 ) {
							$query = "
								DELETE FROM list__Weight
								WHERE weight = ABS({$netWeight})
									AND goodsID = {$goodsID}
									AND RN = {$ReceiptNumber}
								ORDER BY LW_ID DESC
								LIMIT 1
							";
							mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

							// Меняем ID последней регистрации
							$query = "
								UPDATE WeighingTerminal
								SET last_transaction = (SELECT nextID FROM list__Weight WHERE WT_ID = {$deviceID} ORDER BY nextID DESC LIMIT 1)
								WHERE WT_ID = {$deviceID}
							";
							mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						}
						else {
							// Записываем в базу регистрацию
							$query = "
								INSERT INTO list__Weight
								SET weight = {$netWeight}
									,nextID = {$nextID}
									,WT_ID = {$deviceID}
									,weighing_time = '{$transactionDate}'
									,goodsID = {$goodsID}
									,RN = {$ReceiptNumber}
							";
							mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

							// Запоминаем ID последней регистрации
							$query = "
								UPDATE WeighingTerminal
								SET last_transaction = {$nextID}
								WHERE WT_ID = {$deviceID}
							";
							mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						}
					}
				}
				//Закрытие партии
				elseif( $data[$i+10] == 71 or $data[$i+10] == 72 ) {
					//Идентификатор
					$nextID = $data[$i] + ($data[$i+1] << 8) + ($data[$i+2] << 16) + ($data[$i+3] << 24);
					//Номер терминала
					$deviceID = $data[$i+6] + ($data[$i+7] << 8) + ($data[$i+8] << 16) + ($data[$i+9] << 24);
					//Дата/время совершения регистрации
					$receipt_end = sprintf("20%02d-%02d-%02d %02d:%02d:%02d", $data[$i+11], $data[$i+12], $data[$i+13], $data[$i+14], $data[$i+15], $data[$i+16]);

					// Узнаем время предыдущего закрытия
					$query = "
						SELECT last_receiptDate receipt_start
						FROM WeighingTerminal
						WHERE WT_ID = {$deviceID}
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$row = mysqli_fetch_array($res);
					$receipt_start = $row["receipt_start"];

					// Узнаем номер закрытой партии
					$query = "
						SELECT IFNULL(MAX(RN), 0) RN
						FROM list__Weight
						WHERE weighing_time BETWEEN '{$receipt_start}' AND '{$receipt_end}'
							AND WT_ID = {$deviceID}
							AND LO_ID IS NULL
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$row = mysqli_fetch_array($res);
					$RN = $row["RN"];

					// Из пересечения временных интервалов находим наиболее подходящую кассету
					$query = "
						SELECT SUB.LO_ID
							#,TIMESTAMPDIFF(SECOND, IF(SUB.opening_time > '{$receipt_start}', SUB.opening_time, '{$receipt_start}'), IF(SUB.end_time < '{$receipt_end}', SUB.end_time, '{$receipt_end}')) / TIMESTAMPDIFF(SECOND, SUB.opening_time, SUB.end_time) `share`
							,(SELECT SUM(1) FROM list__Weight WHERE RN = {$RN} AND WT_ID = {$deviceID} AND weighing_time BETWEEN SUB.opening_time AND SUB.end_time) CW_cnt
						FROM (
							SELECT (SELECT LO_ID FROM list__Opening WHERE opening_time < LO.opening_time ORDER BY opening_time DESC LIMIT 1) LO_ID
								,(SELECT opening_time FROM list__Opening WHERE opening_time < LO.opening_time ORDER BY opening_time DESC LIMIT 1) opening_time
								,LO.opening_time end_time
							FROM list__Opening LO
							WHERE LO.opening_time > '{$receipt_start}'
								AND (SELECT opening_time FROM list__Opening WHERE opening_time < LO.opening_time ORDER BY opening_time DESC LIMIT 1) <= '{$receipt_end}'
								AND '{$receipt_start}' <= LO.opening_time
							) SUB
						ORDER BY CW_cnt DESC
						LIMIT 1
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$row = mysqli_fetch_array($res);
					$LO_ID = $row["LO_ID"];

					// Связываем регистрации закрытой партии с подходящей по времени кассетой
					$query = "
						UPDATE list__Weight
						SET LO_ID = {$LO_ID}
						WHERE WT_ID = {$deviceID}
							AND LO_ID IS NULL
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

					// Обновляем время закрытия последней партии
					$query = "
						UPDATE WeighingTerminal
						SET last_receiptDate = '{$receipt_end}'
						WHERE WT_ID = {$deviceID}
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

					// Запоминаем ID последней регистрации
					$query = "
						UPDATE WeighingTerminal
						SET last_transaction = {$nextID}
						WHERE WT_ID = {$deviceID}
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}
			}

			// Если это не последняя часть
			if( $nums > $curnum ) {
				read_transaction_LW($ID, ++$curnum, $socket, $mysqli);
			}
		}
	}
	else { //Если CRC не совпали делаем попытку еще
		read_transaction_LW($ID, $curnum, $socket, $mysqli);
	}
}

// Функция читает регистрации с терминала этикеток на паллеты
function read_transaction_LPP($ID, $curnum, $socket, $mysqli) {
	$hexID = sprintf("%02x%02x%02x%02x", ($ID & 0xFF), (($ID >> 8) & 0xFF), (($ID >> 16) & 0xFF), (($ID >> 24) & 0xFF));
	$hexcurnum = sprintf("%02x%02x", ($curnum & 0xFF), (($curnum >> 8) & 0xFF));
	$in = "\xF8\x55\xCE\x0C\x00\x92\x03\x00\x00".hex2bin($hexcurnum).hex2bin($hexID)."\x00\x00";
	$crc = crc16(byteStr2byteArray($in));
	$in .= hex2bin($crc);

	socket_write($socket, $in);

	//Заголовок
	$result = socket_read($socket, 3);

	//Длина тела сообщения
	$length = socket_read($socket, 2);
	$result .= $length;
	$length = hexdec(bin2hex($length));
	$length = (($length & 0xFF) << 8) + (($length >> 8) & 0xFF);

	// Тело сообщения
	$result .= socket_read($socket, $length);

	//Читаем CRC
	$crc = socket_read($socket, 2);

	//Ответ в массив
	$data = byteStr2byteArray($result);

	//Сравниваем CRC
	if( crc16($data) == bin2hex($crc) ) {

		//Если ответ 0x52 CMD_TCP_ACK_TRANSACTION
		if( $data[5] == 0x52 ) {

			//Число частей в файле
			$nums = $data[7] + ($data[8] << 8);

			//Номер текущей части
			$curnum = $data[9] + ($data[10] << 8);

			//Длина записи
			$curlen = $data[11] + ($data[12] << 8);

			for( $i=13; $i < $curlen; $i=$i+104) {
				// Этикетирование паллета
				if( $data[$i+10] == 1 ) {
					//Идентификатор
					$nextID = $data[$i] + ($data[$i+1] << 8) + ($data[$i+2] << 16) + ($data[$i+3] << 24);
					//Номер терминала
					$deviceID = $data[$i+6] + ($data[$i+7] << 8) + ($data[$i+8] << 16) + ($data[$i+9] << 24);
					//Дата/время совершения регистрации
					$transactionDate = sprintf("20%02d-%02d-%02d %02d:%02d:%02d", $data[$i+11], $data[$i+12], $data[$i+13], $data[$i+14], $data[$i+15], $data[$i+16]);
					//ID товара
					$goodsID = $data[$i+37] + ($data[$i+38] << 8) + ($data[$i+39] << 16) + ($data[$i+40] << 24);

					// Записываем в базу регистрацию
					$query = "
						INSERT INTO list__PackingPallet
						SET packed_time = '{$transactionDate}'
							,nextID = {$nextID}
							,WT_ID = {$deviceID}
							,CW_ID = {$goodsID}
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

					// Запоминаем ID последней регистрации
					$query = "
						UPDATE WeighingTerminal
						SET last_transaction = {$nextID}
						WHERE WT_ID = {$deviceID}
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}
				//Закрытие партии
				elseif( $data[$i+10] == 71 or $data[$i+10] == 72 ) {
					//Идентификатор
					$nextID = $data[$i] + ($data[$i+1] << 8) + ($data[$i+2] << 16) + ($data[$i+3] << 24);
					//Номер терминала
					$deviceID = $data[$i+6] + ($data[$i+7] << 8) + ($data[$i+8] << 16) + ($data[$i+9] << 24);
					//Дата/время совершения регистрации
					$receipt_end = sprintf("20%02d-%02d-%02d %02d:%02d:%02d", $data[$i+11], $data[$i+12], $data[$i+13], $data[$i+14], $data[$i+15], $data[$i+16]);

					// Обновляем время закрытия последней партии
					$query = "
						UPDATE WeighingTerminal
						SET last_receiptDate = '{$receipt_end}'
						WHERE WT_ID = {$deviceID}
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

					// Запоминаем ID последней регистрации
					$query = "
						UPDATE WeighingTerminal
						SET last_transaction = {$nextID}
						WHERE WT_ID = {$deviceID}
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}
			}

			// Если это не последняя часть
			if( $nums > $curnum ) {
				read_transaction_LPP($ID, ++$curnum, $socket, $mysqli);
			}
		}
	}
	else { //Если CRC не совпали делаем попытку еще
		read_transaction_LPP($ID, $curnum, $socket, $mysqli);
	}
}
?>
