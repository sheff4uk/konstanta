<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$path = dirname(dirname($argv[0]));
$key = $argv[1];
$CB_ID = $argv[2];
$to = $argv[3];

include $path."/config.php";
// Проверка доступа
if( $key != $script_key ) die('Access denied!');

require $path.'/PHPMailer/PHPMailer.php';
require $path.'/PHPMailer/SMTP.php';
require $path.'/PHPMailer/Exception.php';

//$date = date_create( '-1 days' );
//$date_format = date_format($date, 'd/m/Y');
$date_format = '07/04/2025';
$subject = "Cube test report on {$date_format}";

$message = "
	<html>
	<body>
		<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
			<tr>
				<th><img src='https://konstanta.ltd/assets/images/logo.png' alt='KONSTANTA' style='width: 200px; margin: 5px;'></th>
				<th><n style='font-size: 2em;'>Cube test report</n></th>
				<th>Report date: <n style='font-size: 2em;'>{$date_format}</n></th>
			</tr>
		</table>
";
$message .= "
	<table cellspacing='0' cellpadding='2' border='1' style='table-layout: fixed; width: 100%;'>
		<thead style='word-wrap: break-word;'>
			<tr>
				<th>Part-number</th>
				<th>Time of filling</th>
				<!--<th>Mass of a cube of concrete solution, kg</th>-->
				<th>Time to test</th>
				<th>Mass of the test cube, kg</th>
                <th>Curing time in hours</th>
                <th>Pressure, MPa</th>
                <th>Test result</th>
			</tr>
		</thead>
		<tbody style='text-align: center;'>
";

$query = "
	SELECT DATE_FORMAT(LCT.test_date, '%d/%m/%Y') test_date
		,DATE_FORMAT(LCT.test_time, '%H:%i') test_time
		,CW.drawing_item
		,DATE_FORMAT(LB.batch_date, '%d/%m/%Y') batch_date_format
		,DATE_FORMAT(LB.batch_time, '%H:%i') batch_time_format
		,TIMESTAMPDIFF(HOUR, CAST(CONCAT(LB.batch_date, ' ', LB.batch_time) as datetime), CAST(CONCAT(LCT.test_date, ' ', LCT.test_time) as datetime)) delay_fact
		,ROUND(LB.mix_density / 1000, 2) mix_density
		,ROUND(LCT.cube_weight / 1000, 2) cube_weight
		,LCT.pressure
        ,IF(LCT.delay = 24, IF(LCT.pressure < 20, 1, 0), IF(LCT.pressure < 30, 1, 0)) press_error
	FROM list__CubeTest LCT
	JOIN list__Batch LB ON LB.LB_ID = LCT.LB_ID
	JOIN plan__Batch PB ON PB.PB_ID = LB.PB_ID
	JOIN CounterWeight CW ON CW.CW_ID = PB.CW_ID AND CB_ID = {$CB_ID}
	WHERE LCT.test_date LIKE '2025-04-07'
	ORDER BY LCT.test_date DESC, LCT.test_time DESC
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$need = $row["current_need"] - $row["shell_balance"];
    $result = ($row["press_error"] ? "failed" : "passed");
	$message .= "
		<tr>\n
			<td style='background-color: #ccc'>{$row["drawing_item"]}</td>
			<td style='background-color: #ccc'>{$row["batch_date_format"]} {$row["batch_time_format"]}</td>
			<!--<td style='background-color: #ccc'>{$row["mix_density"]}</td>-->
			<td>{$row["test_date"]} {$row["test_time"]}</td>
			<td>{$row["cube_weight"]}</td>
			<td>{$row["delay_fact"]}</td>
			<td>{$row["pressure"]}</td>
			<td>{$result}</td>
		</tr>
	";
}

$message .= "
		</tbody>
	</table>
";

$message .= "
			<p>This letter is generated automatically. Please do not answer it. If you have any questions, you can contact us by e-mail info@konstanta.ltd.</p>
		</body>
	</html>
";

$mail = new PHPMailer();

$mail->isSMTP();
$mail->Host			= 'exchange.atservers.net'; 
$mail->SMTPAuth		= true;
$mail->Username		= $phpmailer_email;
$mail->Password		= $phpmailer_secret;
$mail->SMTPSecure	= 'tls';
$mail->Port			= 587;

$mail->setFrom($phpmailer_email, 'KONSTANTA');

foreach (explode(",", $to) as &$value) {
	$mail->addBCC($value);
	//$mail->addAddress($value);
}

$mail->CharSet = 'UTF-8';
$mail->isHTML(true);
$mail->Subject = $subject;
$mail->Body    = $message;

$mail->send();
?>
