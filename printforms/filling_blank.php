<?php
include "../config.php";
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<script src="../js/jquery-1.11.3.min.js"></script>
	<!-- <script src="https://kit.fontawesome.com/020f21ae61.js" crossorigin="anonymous"></script> -->
	<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> -->
		<link rel="stylesheet" type='text/css' href="../assets/fontawesome/css/all.min.css">

<?php
$PB_ID = $_GET["PB_ID"];

$query = "
	SELECT PB.F_ID
		,PB.year
		,PB.cycle
		,PB.CW_ID
		,PB.batches
		,CW.item
		,MF.fillings
		,MF.per_batch
		,MF.cubetests
		,CONCAT(ROUND(MF.min_density/1000, 2), '&ndash;', ROUND(MF.max_density/1000, 2)) spec
		,MF.water
	FROM plan__Batch PB
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID
	JOIN MixFormula MF ON MF.CW_ID = CW.CW_ID AND MF.F_ID = PB.F_ID
	WHERE PB_ID = {$PB_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);

$F_ID = $row["F_ID"];
$batches = $row["batches"];
$item = $row["item"];
$year = $row["year"];
$cycle = $row["cycle"];
$fillings = $row["fillings"];
$per_batch = $row["per_batch"];
$cubetests = $row["cubetests"];
$CW_ID = $row["CW_ID"];
$spec = $row["spec"];
$water = $row["water"];

// Массив с номерами контрольных замесов (кубы)
if( $batches > 1 ) {
	if( $cubetests == 1 ) {
		$tests = array(round($batches/2)+1);
	}
	elseif( $cubetests == 2 ) {
		$tests = array(2, round($batches/2)+1);
	}
	elseif( $cubetests == 3 ) {
		$tests = array(2, round($batches/2)+1, $batches);
	}
}
else {
	$tests = array(1);
}

echo "<title>Чеклист оператора для {$item} цикл {$year}/{$cycle}</title>";
?>
	<style>
		@media print {
			@page {
				size: landscape;
/*				padding: 0;*/
/*				margin: 0;*/
			}
		}

		body, td {
			font-family: Trebuchet MS, Tahoma, Verdana, Arial, sans-serif;
			font-size: 10pt;
		}
		table {
			table-layout: fixed;
			width: 100%;
			border-collapse: collapse;
			border-spacing: 0px;
		}
		.thead {
			text-align: center;
			font-weight: bold;
		}
		td, th {
			padding: 3px;
			border: 1px solid black;
			line-height: 1em;
		}
		.nowrap {
			white-space: nowrap;
		}
		.cassette1 {
			font-weight: bold;
			border: 1px solid #333;
			border-radius: 5px;
			margin: 0 2px;
			padding: 2px;
			display: inline-block;
			width: 28px;
		}
		.cassette {
			background-color: #333;
			color: #fff;
			border-radius: 5px;
			border: 2px solid #333;
			margin: 0 2px;
			display: inline-block;
			font-weight: bold;
			-webkit-print-color-adjust: exact;
			print-color-adjust: exact;
		}
	</style>

	<script>
		function fontSize(elem, maxFontSize) {
			var fontSize = $(elem).attr('fontSize');
			var width = $(elem).width();
			var bodyWidth = $(elem).parent().width();
			var multiplier = bodyWidth / width;
			fontSize = Math.floor(fontSize * multiplier);
			if( fontSize > maxFontSize ) fontSize = maxFontSize;
			$(elem).css({fontSize: fontSize+'px'});
			$(elem).attr('fontSize', fontSize);
		}
	</script>
</head>
<body>

<!--
	<b style="float: right; width: 55%;"><span style="font-size: 1.5em;">ВНИМАНИЕ!</span> Время замеса не должно выходить за границы текущего года.</b>
<h3>Фактическая дата первого замеса: ________________</h3>
-->

<table>
	<thead>
		<tr>
			<th><img src="/img/logo.png" alt="KONSTANTA" style="width: 200px; margin: 5px;"></th>
			<th width="250" style="position: relative;"><span style="position: absolute; top: 0px; left: 5px;" class="nowrap">деталь</span><n id="item" style="font-size: 3em;" fontSize="40" class="nowrap"><?=$item?></n></th>
			<th width="175" style="position: relative;"><span style="position: absolute; top: 0px; left: 5px;">цикл</span><n style="font-size: 3em;"><?=$year?>-<?=$cycle?></n></th>
			<th width="200" style="position: relative;">
				<img src="../barcode.php?code=<?=$PB_ID?>&w=200&h=60" alt="barcode">
				<span style="position: absolute; background: white; left: calc(50% - 40px); top: 48px; width: 80px;"><?=str_pad($PB_ID, 8, "0", STR_PAD_LEFT)?></span>
			</th>
		</tr>
<?php
	// Формируем список зарезервированных кассет
	$query = "
		SELECT cassette
			,1 - 0.05 * LEAST(10, IFNULL(last_filling_days(cassette), 99)) opacity
		FROM Cassettes
		WHERE F_ID = {$F_ID}
			AND CW_ID = {$CW_ID}
		ORDER BY opacity DESC, cassette
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$cassettes .= "<n class='cassette' style='opacity: {$row["opacity"]};'>{$row["cassette"]}</n>";
	}
?>
		<tr>
			<th colspan="4" style="position: relative; padding-top: 12px;">
				<span style="position: absolute; top: 0px; left: 5px;" class="nowrap">Зарезервированные кассеты:</span>
				<?=$cassettes?>
			</th>
		</tr>
	</thead>
</table>

<?php
// Данные рецепта
$query = "
	SELECT MN.material_name
		,MFM.quantity
		,2 - MN.checkbox_density rowspan
		,PBD.density
		,MN.checkbox_density
		,MN.MN_ID
		,MN.color
		,MN.admission
	FROM plan__Batch PB
	JOIN MixFormula MF ON MF.CW_ID = PB.CW_ID AND MF.F_ID = PB.F_ID
	JOIN MixFormulaMaterial MFM ON MFM.MF_ID = MF.MF_ID
	JOIN material__Name MN ON MN.MN_ID = MFM.MN_ID
	LEFT JOIN plan__BatchDensity PBD ON PBD.PB_ID = PB.PB_ID
		AND PBD.MN_ID = MFM.MN_ID
	WHERE PB.PB_ID = {$PB_ID}
	ORDER BY MN.material_name
";
?>

<table>
	<thead style="word-wrap: break-word;">
		<tr>
			<th rowspan="3" width="30">№<br>п/п</th>
			<th rowspan="2"><sup style="float: left;">Дата замеса:</sup><br>__________</th>
			<th  rowspan="2">Масса куба раствора</th>
			<th rowspan="3" width="30" style="border-right: 4px solid;">t, ℃ 22±8</th>

<?php
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		echo "<th rowspan='{$row["rowspan"]}'>{$row["material_name"]}, кг</th>";
	}
?>

			<th>Вода, л</th>
			<th rowspan="3" colspan="<?=$fillings?>" width="<?=($fillings * 50)?>" style="border-left: 4px solid;">№ кассеты</th>
			<th rowspan="3" width="40">Недолив</th>
			<th rowspan="3" width="20"><i class="fas fa-cube"></i></th>
		</tr>
		<tr>

<?php
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		if ($row["checkbox_density"]) {
			echo "<th style='text-align: left; border: dashed;'><sup>куб:</sup></th>";
		}
	}

?>
			<th style='text-align: left; border: dashed;'></th>
		</tr>
		<tr>
			<th>Время<br>замеса</th>
			<th class="nowrap"><?=$spec?> кг</th>
<?php
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		echo "<th class='nowrap'>{$row["quantity"]}±{$row["admission"]}</th>";
	}

?>
			<th class='nowrap'>min <?=$water?></th>
		</tr>
	</thead>
	<tbody>
<?php
$fillings_cell = "<td rowspan='{$per_batch}' style='border-left: 4px solid;'></td>";
for ($i = 1; $i <= $fillings; $i++) {
	$fillings_cell .= "<td rowspan='{$per_batch}'></td>";
}

$j = 0;

for ($i = 1; $i <= $batches; $i++) {
	echo "
		<tr>
			<td style='text-align: center;'>{$i}</td>
			<td style='text-align: center;'>__:__</td>
			<td></td>
			<td style='border-right: 4px solid;'></td>
	";

	$query = "
		SELECT MFM.MN_ID
		FROM MixFormula MF
		JOIN MixFormulaMaterial MFM ON MFM.MF_ID = MF.MF_ID
		WHERE MF.F_ID = {$F_ID}
			AND MF.CW_ID = {$CW_ID}
	";
	$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subsubrow = mysqli_fetch_array($subsubres) ) {
		echo "<td></td>";
	}

	echo "
			<td></td>
			".($j == 0 ? $fillings_cell : "")."
			<td style='text-align: center;'>".(in_array($i, $tests) ? "<b style='font-size: 1.4em;'>&#10065;</b>" : "&#10065;")."</td>
		</tr>
	";
	$j++;
	$j = ($j == $per_batch ? 0 : $j);
}
?>
	</tbody>
</table>

<script>
	fontSize('#item', 45);
</script>

</body>
</html>
