<?php
include_once "../checkrights.php";

$year = 2025;
$month = 10;
$half = 2;

// Узнаем кол-во дней в выбранном месяце
$strdate = '01.'.$month.'.'.$year;
$timestamp = strtotime($strdate);
$days = date('t', $timestamp);

$cardcode = $_GET["cardcode"];
$F_ID = $_GET["F_ID"];

// Узнаем имя работникак
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

setlocale(LC_ALL, 'ru_RU', 'ru_RU.UTF-8', 'ru', 'russian');
$html = "<h1>{$row["Surname"]} {$row["Name"]} (16...{$days} ".(strftime("%B %Y", $timestamp)).")</h1>";

// Проверяем долг за первый полумесяц
if ($half == 2) {
    $query = "
        SELECT sigmapay(year, month, USR_ID, F_ID, 1) - IFNULL(TM.salary1, 0) sigmapay1
        FROM TariffMonth TM
        WHERE TM.year = {$year}
            AND TM.month = {$month}
            AND TM.USR_ID = {$USR_ID}
            AND TM.F_ID = {$F_ID}
    ";
    $res = mysqli_query( $mysqli, $query );
    $row = mysqli_fetch_array($res);
    $sigmapay1 = $row["sigmapay1"];
    if ($sigmapay1 < 0) {
        $html .= "<h2>Долг за прошлый период: <font color='red'>₽".number_format(abs($row["sigmapay1"]), 0, '', ' ')."</font></h2>";
    }
}

// Список начислений, займов и штрафов
$query = "
    SELECT Friendly_date(TS.ts_date) friendly_date
        ,(SELECT SUM(pay) FROM TimesheetShift WHERE TS_ID = TS.TS_ID AND IFNULL(approved, 1) = 1) pay
        ,TS.fine
        ,TS.payout
        ,TS.comment
    FROM Timesheet TS
    WHERE 1
        AND YEAR(TS.ts_date) = {$year}
        AND MONTH(TS.ts_date) = {$month}
        AND DAY(TS.ts_date) > 15
        AND TS.USR_ID = {$USR_ID}
        AND TS.F_ID = {$F_ID}
    HAVING IFNULL(payout, 0) > 0 OR IFNULL(fine, 0) > 0 OR IFNULL(pay, 0) > 0
    ORDER BY TS.ts_date
";
$res = mysqli_query( $mysqli, $query );
$html .= "
    <table>\n
        <thead>\n
            <tr>\n
                <th>Дата</th>\n
                <th>Начисление</th>\n
                <th>Штраф за прогул</th>\n
                <th>Займ</th>\n
                <th>Примечание</th>\n
            </tr>\n
        </thead>\n
        <tbody>\n
";
while ($row = mysqli_fetch_array($res)) {
    $html .= "
        <tr>\n
            <td>{$row["friendly_date"]}</td>\n
            <td class='txtright'>{$row["pay"]}</td>\n
            <td class='txtright'><font color='red'>{$row["fine"]}</font></td>\n
            <td class='txtright'><font color='red'>{$row["payout"]}</font></td>\n
            <td>{$row["comment"]}</td>\n
        </tr>\n
    ";
    $total_pay += $row["pay"];
    $total_fine += $row["fine"];
    $total_payout += $row["payout"];
}
$html .= "
            <tr style='font-weight: bold;'>\n
                <td>Всего:</td>\n
                <td class='txtright'>{$total_pay}</td>\n
                <td class='txtright'><font color='red'>{$total_fine}</font></td>\n
                <td class='txtright'><font color='red'>{$total_payout}</font></td>\n
                <td></td>\n
            </tr>\n
        </tbody>\n
    </table>\n
";

$query = "
	SELECT TM.Salary2 Salary
        ,sigmapay(year, month, USR_ID, F_ID, 2) - IFNULL(salary2, 0) + IF(sigmapay(year, month, USR_ID, F_ID, 1) - IFNULL(salary1, 0) < 0, sigmapay(year, month, USR_ID, F_ID, 1) - IFNULL(salary1, 0), 0) payment
	FROM TariffMonth TM
	WHERE TM.year = {$year}
        AND TM.month = {$month}
        AND TM.USR_ID = {$USR_ID}
        AND TM.F_ID = {$F_ID}
";
$res = mysqli_query( $mysqli, $query );
$row = mysqli_fetch_array($res);
$Salary = $row["Salary"];
$payment = $row["payment"];

$html .= "<h2>Выдано: ₽".(number_format($row["Salary"], 0, '', ' '))."</h2>";
$html .= "<h1>К выдаче: ₽".(number_format($row["payment"], 0, '', ' '))."</h2>";

$html .= "<input type=\"hidden\" name=\"payment\" value=\"{$payment}\">";
$html .= "<input type=\"hidden\" name=\"ye\" value=\"{$year}\">";
$html .= "<input type=\"hidden\" name=\"mn\" value=\"{$month}\">";
$html .= "<input type=\"hidden\" name=\"USR_ID\" value=\"{$USR_ID}\">";

$html = str_replace("\n", "", addslashes($html));
echo "$('#salary_payment_form fieldset').html('{$html}');";
?>
