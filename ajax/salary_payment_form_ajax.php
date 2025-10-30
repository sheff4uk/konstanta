<?php
include_once "../checkrights.php";

$year = 2025;
$month = 10;
//$half = 2;

$cardcode = $_GET["cardcode"];
$F_ID = $_GET["F_ID"];

$query = "
	SELECT USR_ID
        ,Surname
        ,Name
	FROM Users
	WHERE cardcode LIKE '{$cardcode}'
";
$res = mysqli_query( $mysqli, $query );
$row = mysqli_fetch_array($res);
$USR_ID = $row["USR_ID"];

$html = "<h2>{$row["Surname"]} {$row["Name"]}</h2>";

$query = "
	SELECT Salary2 Salary
	FROM TariffMonth
	WHERE year = {$year}
        AND month = {$month}
        AND USR_ID = {$USR_ID}
        AND F_ID = {$F_ID}
";
$res = mysqli_query( $mysqli, $query );
$row = mysqli_fetch_array($res);
$Salary = $row["Salary"];

$html .= "<h1>Выдано: ₽{$row["Salary"]}</h2>";

$query = "
    SELECT sigmapay(year, month, USR_ID, F_ID, 2) - IFNULL(salary2, 0) + IF(sigmapay(year, month, USR_ID, F_ID, 1) - IFNULL(salary1, 0) < 0, sigmapay(year, month, USR_ID, F_ID, 1) - IFNULL(salary1, 0), 0) payment
    FROM TariffMonth
    WHERE 1
        AND year = {$year}
        AND month = {$month}
        AND USR_ID = {$USR_ID}
        AND F_ID = {$F_ID}
";
$res = mysqli_query( $mysqli, $query );
$row = mysqli_fetch_array($res);
$payment = $row["payment"];

$html .= "<h1>К выдаче: ₽{$row["payment"]}</h2>";

$html .= "<input type=\"hidden\" name=\"payment\" value=\"{$payment}\">";
$html .= "<input type=\"hidden\" name=\"ye\" value=\"{$year}\">";
$html .= "<input type=\"hidden\" name=\"mn\" value=\"{$month}\">";
$html .= "<input type=\"hidden\" name=\"USR_ID\" value=\"{$USR_ID}\">";

$html = str_replace("\n", "", addslashes($html));
echo "$('#salary_payment_form fieldset').html('{$html}');";
?>
