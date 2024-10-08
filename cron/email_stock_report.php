<?php
$path = dirname(dirname($argv[0]));
$key = $argv[1];
$to = $argv[2];

include $path."/config.php";
// Проверка доступа
if( $key != $script_key ) die('Access denied!');

$date = date_create();
$date_format = date_format($date, 'd.m.Y');
$subject = "=?utf-8?b?". base64_encode("[KONSTANTA] Складской остаток {$date_format}"). "?=";

$message = "
	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<tr>
			<th><img src='https://konstanta.ltd/assets/images/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></th>
			<th><n style='font-size: 2em;'>Складской остаток</n></th>
			<th>{$date_format}</th>
		</tr>
	</table>

	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<thead style='word-wrap: break-word;'>
			<tr>
				<th>Комплект противовесов</th>
				<th>Паллетов</th>
				<th>Деталей</th>
			</tr>
		</thead>
		<tbody style='text-align: center;'>
";

$query = "
	SELECT CONCAT(IFNULL(CW.item, CWP.cwp_name), ' (', CWP.in_pallet, 'шт)') item
		,SUM(1) pallets
		,SUM(CWP.in_pallet) details
	FROM list__PackingPallet LPP
	JOIN CounterWeightPallet CWP ON CWP.CWP_ID = LPP.CWP_ID
	LEFT JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
	WHERE LPP.shipment_time IS NULL AND LPP.removal_time IS NULL
		AND LPP.F_ID = 1
		AND IFNULL(CW.CB_ID, 0) != 5
	GROUP BY LPP.CWP_ID
	ORDER BY LPP.CWP_ID ASC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query1: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$message .= "
		<tr>
			<td>{$row["item"]}</td>
			<td>{$row["pallets"]}</td>
			<td>{$row["details"]}</td>
		</tr>
	";
}

$message .= "
		</tbody>
	</table>
";

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=\"utf-8\"\r\n";
$headers .= "From: planner@konstanta.ltd\r\n";

mail($to, $subject, $message, $headers);
?>
