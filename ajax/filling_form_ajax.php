<?
include_once "../checkrights.php";

$max_batches = 30; // Максимально возможное число замесов
$PB_ID = $_GET["PB_ID"];
$query = "
	SELECT PB.year
		,PB.cycle
		,PB.CW_ID
		,PB.batches
		,PB.fact_batches
		,CW.item
		,IFNULL(PB.fillings_per_batch, CW.fillings) fillings
		,IFNULL(PB.in_cassette, CW.in_cassette) in_cassette
		,CONCAT(ROUND(CW.min_density/1000, 2), '&ndash;', ROUND(CW.max_density/1000, 2)) spec
		,MIN(LB.batch_date) batch_date
	FROM plan__Batch PB
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	LEFT JOIN list__Batch LB ON LB.PB_ID = PB.PB_ID
	WHERE PB.PB_ID = {$PB_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);

$year = $row["year"];
$cycle = $row["cycle"];
$batches = $row["batches"];
$fact_batches = $row["fact_batches"];
$item = $row["item"];
$fillings = $row["fillings"];
$in_cassette = $row["in_cassette"];
$CW_ID = $row["CW_ID"];
$spec = $row["spec"];
$batch_date = $row["batch_date"];

$html = "
	<input type='hidden' name='PB_ID' value='{$PB_ID}'>
	<p style='display: none; text-align: center; font-size: 2em;'>Число замесов: <input type='number' name='fact_batches' id='rows' min='".($fact_batches ? $fact_batches : "1")."' max='{$max_batches}' value='".($fact_batches ? $fact_batches : $batches)."'></p>
	<input type='hidden' name='PB_ID' value='{$PB_ID}'>
	<input type='hidden' name='fillings_per_batch' value='{$fillings}'>
	<input type='hidden' name='in_cassette' value='{$in_cassette}'>
	<table style='table-layout: fixed; width: 100%; border-collapse: collapse; border-spacing: 0px; text-align: center;'>
		<tr>
			<td style='border: 1px solid black; line-height: 1em;'><img src='/img/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></td>
			<td style='font-size: 2em; border: 1px solid black; line-height: 1em;'>{$item}</td>
			<td style='border: 1px solid black; line-height: 1em;'><n style='font-size: 2em;'>{$year}</n> год</td>
			<td style='border: 1px solid black; line-height: 1em;' width='120'><n style='font-size: 2em;'>{$cycle}</n><br>цикл</td>
		</tr>
	</table>
";

// Данные рецепта
$query = "
	SELECT GROUP_CONCAT(CONCAT('<span style=\'font-size: 1.5em;\' class=\'nowrap\'>', MF.letter, '</span>') ORDER BY MF.letter SEPARATOR '<br>') ltr
		,GROUP_CONCAT(CONCAT(ROUND(MF.io_min/1000, 2), '&ndash;', ROUND(MF.io_max/1000, 2)) ORDER BY MF.letter SEPARATOR '<br>') io
		,GROUP_CONCAT(CONCAT(ROUND(MF.sn_min/1000, 2), '&ndash;', ROUND(MF.sn_max/1000, 2)) ORDER BY MF.letter SEPARATOR '<br>') sn
		,GROUP_CONCAT(CONCAT(ROUND(MF.cs_min/1000, 2), '&ndash;', ROUND(MF.cs_max/1000, 2)) ORDER BY MF.letter SEPARATOR '<br>') cs
		,GROUP_CONCAT(IFNULL(CONCAT(MF.iron_oxide, ' ±5'), 0) ORDER BY MF.letter SEPARATOR '<br>') iron_oxide
		,GROUP_CONCAT(IFNULL(CONCAT(MF.sand, ' ±5'), 0) ORDER BY MF.letter SEPARATOR '<br>') sand
		,GROUP_CONCAT(IFNULL(CONCAT(MF.crushed_stone, ' ±5'), 0) ORDER BY MF.letter SEPARATOR '<br>') crushed_stone
		,GROUP_CONCAT(IFNULL(CONCAT(MF.cement, ' ±2'), 0) ORDER BY MF.letter SEPARATOR '<br>') cement
		,GROUP_CONCAT(IFNULL(CONCAT(MF.plasticizer, ' ±0.1'), 0) ORDER BY MF.letter SEPARATOR '<br>') plasticizer
		,GROUP_CONCAT(IFNULL(CONCAT('min ', MF.water), 0) ORDER BY MF.letter SEPARATOR '<br>') water
		,COUNT(MF.iron_oxide) io_cnt
		,COUNT(MF.sand) sn_cnt
		,COUNT(MF.crushed_stone) cs_cnt
		,COUNT(MF.cement) cm_cnt
		,COUNT(MF.plasticizer) pl_cnt
		,COUNT(MF.water) wt_cnt
	FROM MixFormula MF
	WHERE MF.CW_ID = {$CW_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);

$html .= "
	<table style='font-size: .85em; table-layout: fixed; width: 100%; border-collapse: collapse; border-spacing: 0px; text-align: center;'>
		<thead style='word-wrap: break-word;'>
			<tr>
				<th rowspan='3' width='30'>№<br>п/п</th>
				<th rowspan='3' width='160'>Дата и время замеса</th>
				<th rowspan='2' width='30' style='word-wrap: break-word;'>Рецепт</th>
				<th colspan='".(1 + ($row["io"] ? 1 : 0) + ($row["sn"] ? 1 : 0) + ($row["cs"] ? 1 : 0))."'>Масса куба, кг</th>
				<th rowspan='3' width='40'>t, ℃ 22±8</th>
				".($row["io_cnt"] ? "<th rowspan='2'>Окалина, кг</th>" : "")."
				".($row["sn_cnt"] ? "<th rowspan='2'>КМП, кг</th>" : "")."
				".($row["cs_cnt"] ? "<th rowspan='2'>Отсев, кг</th>" : "")."
				".($row["cm_cnt"] ? "<th rowspan='2'>Цемент, кг</th>" : "")."
				".($row["pl_cnt"] ? "<th rowspan='2'>Пластификатор, кг</th>" : "")."
				".($row["wt_cnt"] ? "<th rowspan='2'>Вода, кг</th>" : "")."
				<th rowspan='3' colspan='{$fillings}' width='".($fillings * 50)."'>№ кассеты</th>
				<th rowspan='3'>Недолив</th>
				<th rowspan='3' width='30'><i class='fas fa-cube' title='Испытание куба'></i></th>
				<th rowspan='3'>Оператор</th>
			</tr>
			<tr>
				".(($row["io"] ? "<th>Окалины</th>" : ""))."
				".(($row["sn"] ? "<th>КМП</th>" : ""))."
				".(($row["cs"] ? "<th>Отсева</th>" : ""))."
				<th>Раствора</th>
			</tr>
			<tr>
				<th>{$row["ltr"]}</th>
				".(($row["io"] ? "<th class='nowrap'>{$row["io"]}</th>" : ""))."
				".(($row["sn"] ? "<th class='nowrap'>{$row["sn"]}</th>" : ""))."
				".(($row["cs"] ? "<th class='nowrap'>{$row["cs"]}</th>" : ""))."
				<th class='nowrap'>{$spec}</th>
				".($row["io_cnt"] ? "<th class='nowrap'>{$row["iron_oxide"]}</th>" : "")."
				".($row["sn_cnt"] ? "<th class='nowrap'>{$row["sand"]}</th>" : "")."
				".($row["cs_cnt"] ? "<th class='nowrap'>{$row["crushed_stone"]}</th>" : "")."
				".($row["cm_cnt"] ? "<th class='nowrap'>{$row["cement"]}</th>" : "")."
				".($row["pl_cnt"] ? "<th class='nowrap'>{$row["plasticizer"]}</th>" : "")."
				".($row["wt_cnt"] ? "<th class='nowrap'>{$row["water"]}</th>" : "")."
			</tr>
		</thead>
		<tbody>
";

// Выводим сохраненные замесы в случае редактирования
if( $fact_batches ) {
	$query = "
		SELECT LB.LB_ID
			,LB.OP_ID
			,LB.batch_date
			,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time_format
			,LB.io_density
			,LB.sn_density
			,LB.mix_density
			,LB.temp
			,IFNULL(LB.iron_oxide, 0) iron_oxide
			,IFNULL(LB.sand, 0) sand
			,IFNULL(LB.crushed_stone, 0) crushed_stone
			,IFNULL(LB.cement, 0) cement
			,IFNULL(LB.plasticizer, 0) plasticizer
			,IFNULL(LB.water, 0) water
			,SUM(LF.underfilling) underfilling
			,LB.test
			,IF(LCT.LCT_ID, 1, 0) is_test
		FROM list__Batch LB
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		JOIN Operator OP ON OP.OP_ID = LB.OP_ID
		LEFT JOIN list__CubeTest LCT ON LCT.LB_ID = LB.LB_ID
		WHERE LB.PB_ID = {$PB_ID}
		GROUP BY LB.LB_ID
		ORDER BY LB.batch_date, LB.batch_time
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$i = 0;
	while( $subrow = mysqli_fetch_array($subres) ) {
		$i++;
		// Номера кассет
		$query = "
			SELECT LF.LF_ID
				,LF.cassette
			FROM list__Filling LF
			WHERE LF.LB_ID = {$subrow["LB_ID"]}
			ORDER BY LF.LF_ID
		";
		$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$fillings_cell = "";
		while( $subsubrow = mysqli_fetch_array($subsubres) ) {
			$fillings_cell .= "<td><input type='number' min='1' max='{$cassetts}' name='cassette[{$subrow["LB_ID"]}][{$subsubrow["LF_ID"]}]' value='{$subsubrow["cassette"]}' style='width: 110%; background-color: coral;' required ".($subsubrow["is_link"] ? "readonly" : "")."></td>";
		}

		// Дропдаун операторов
		$operators = "
			<select name='OP_ID[{$subrow["LB_ID"]}]' style='width: 100%;' required>
				<option value=''></option>
		";
		$query = "
			SELECT OP.OP_ID, OP.name
			FROM Operator OP
		";
		$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $subsubrow = mysqli_fetch_array($subsubres) ) {
			$selected = ($subsubrow["OP_ID"] == $subrow["OP_ID"]) ? "selected" : "";
			$operators .= "<option value='{$subsubrow["OP_ID"]}' {$selected}>{$subsubrow["name"]}</option>";
		}
		$operators .= "
			</select>
		";

		$html .= "
			<tr class='batch_row' num='{$i}'>
				<td style='text-align: center; font-size: 1.2em;'>{$i}</td>
				<td><input type='datetime-local' name='batch_time[{$subrow["LB_ID"]}]' style='width: 110%;' value='{$subrow["batch_date"]}T{$subrow["batch_time_format"]}' required></td>
				<td><att></att></td>
				".($row["io"] ? "<td><input type='number' min='2' max='3' step='0.01' name='io_density[{$subrow["LB_ID"]}]' value='".($subrow["io_density"]/1000)."' style='width: 110%;' required></td>" : "")."
				".($row["sn"] ? "<td><input type='number' min='1' max='2' step='0.01' name='sn_density[{$subrow["LB_ID"]}]' value='".($subrow["sn_density"]/1000)."' style='width: 110%;' required></td>" : "")."
				<td><input type='number' min='2' max='4' step='0.01' name='mix_density[{$subrow["LB_ID"]}]' value='".($subrow["mix_density"]/1000)."' style='width: 110%;' required></td>
				<td><input type='number' min='5' max='45' name='temp[{$subrow["LB_ID"]}]' value='{$subrow["temp"]}' style='width: 110%;' required></td>
				".($row["io_cnt"] ? "<td style='background: #a52a2a80;'><input type='number' min='0' name='iron_oxide[{$subrow["LB_ID"]}]' value='{$subrow["iron_oxide"]}' style='width: 110%;' required></td>" : "")."
				".($row["sn_cnt"] ? "<td style='background: #f4a46082;'><input type='number' min='0' name='sand[{$subrow["LB_ID"]}]' value='{$subrow["sand"]}' style='width: 110%;' required></td>" : "")."
				".($row["cs_cnt"] ? "<td style='background: #8b45137a;'><input type='number' min='0' name='crushed_stone[{$subrow["LB_ID"]}]' value='{$subrow["crushed_stone"]}' style='width: 110%;' required></td>" : "")."
				".($row["cm_cnt"] ? "<td style='background: #7080906b;'><input type='number' min='0' name='cement[{$subrow["LB_ID"]}]' value='{$subrow["cement"]}' style='width: 110%;' required></td>" : "")."
				".($row["pl_cnt"] ? "<td style='background: #80800080;'><input type='number' min='0' step='0.01' name='plasticizer[{$subrow["LB_ID"]}]' value='{$subrow["plasticizer"]}' style='width: 110%;' required></td>" : "")."
				".($row["wt_cnt"] ? "<td style='background: #1e90ff85;'><input type='number' min='0' name='water[{$subrow["LB_ID"]}]' value='{$subrow["water"]}' style='width: 110%;' required></td>" : "")."
				{$fillings_cell}
				<td><input type='number' min='0' max='{$in_cassette}' name='underfilling[{$subrow["LB_ID"]}]' value='{$subrow["underfilling"]}' style='width: 110%;' required></td>
				<td class='nowrap'><input type='checkbox' name='test[{$subrow["LB_ID"]}]' ".($subrow["test"] ? "checked" : "")." ".($subrow["is_test"] ? "onclick='return false;'" : "")." value='1'>".($subrow["is_test"] ? "<i id='test_notice' class='fas fa-question-circle' title='Не редактируется так как есть связанные испытания куба.'></i>" : "")."</td>
				<td>{$operators}</td>
			</tr>
		";
	}
}
// Выводим пустые строки
for ($i = $fact_batches + 1; $i <= $max_batches; $i++) {
	// Номера кассет
	$fillings_cell = '';
	for ($j = 1; $j <= $fillings; $j++) {
		$fillings_cell .= "<td><input type='number' min='1' max='{$cassetts}' name='cassette[n_{$i}][{$j}]' style='width: 110%; background-color: coral;' required></td>";
	}

	// Дропдаун операторов
	$operators = "
		<select name='OP_ID[n_{$i}]' style='width: 100%;' required>
			<option value=''></option>
	";
	$query = "
		SELECT OP.OP_ID, OP.name
		FROM Operator OP
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$operators .= "<option value='{$subrow["OP_ID"]}'>{$subrow["name"]}</option>";
	}
	$operators .= "
		</select>
	";

	$html .= "
		<tr class='batch_row' num='{$i}'>
			<td style='text-align: center; font-size: 1.2em;'>{$i}</td>
			<td><input type='datetime-local' name='batch_time[n_{$i}]' style='width: 110%;' required></td>
			<td><att></att></td>
			".($row["io"] ? "<td><input type='number' min='2' max='3' step='0.01' name='io_density[n_{$i}]' style='width: 110%;' required></td>" : "")."
			".($row["sn"] ? "<td><input type='number' min='1' max='2' step='0.01' name='sn_density[n_{$i}]' style='width: 110%;' required></td>" : "")."
			<td><input type='number' min='2' max='4' step='0.01' name='mix_density[n_{$i}]' style='width: 110%;' required></td>
			<td><input type='number' min='5' max='45' name='temp[n_{$i}]' style='width: 110%;' required></td>
			".($row["io_cnt"] ? "<td style='background: #a52a2a80;'><input type='number' min='0' name='iron_oxide[n_{$i}]' style='width: 110%;' required></td>" : "")."
			".($row["sn_cnt"] ? "<td style='background: #f4a46082;'><input type='number' min='0' name='sand[n_{$i}]' style='width: 110%;' required></td>" : "")."
			".($row["cs_cnt"] ? "<td style='background: #8b45137a;'><input type='number' min='0' name='crushed_stone[n_{$i}]' style='width: 110%;' required></td>" : "")."
			".($row["cm_cnt"] ? "<td style='background: #7080906b;'><input type='number' min='0' name='cement[n_{$i}]' style='width: 110%;' required></td>" : "")."
			".($row["pl_cnt"] ? "<td style='background: #80800080;'><input type='number' min='0' step='0.01' name='plasticizer[n_{$i}]' style='width: 110%;' required></td>" : "")."
			".($row["wt_cnt"] ? "<td style='background: #1e90ff85;'><input type='number' min='0' name='water[n_{$i}]' style='width: 110%;' required></td>" : "")."
			{$fillings_cell}
			<td><input type='number' min='0' max='{$in_cassette}' name='underfilling[n_{$i}]' style='width: 110%;' required></td>
			<td><input type='checkbox' name='test[n_{$i}]' value='1'></td>
			<td>{$operators}</td>
		</tr>
	";
}

$html .= "
		</tbody>
	</table>
";

$html = str_replace("\n", "", addslashes($html));
echo "$('#filling_form fieldset').html('{$html}');";
?>
