<?php
include "../config.php";
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<script src="../js/jquery-1.11.3.min.js"></script>
	<!-- <script src="https://kit.fontawesome.com/020f21ae61.js" crossorigin="anonymous"></script> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<?php
$PS_ID = $_GET["PS_ID"];
echo "<title>Накладная №{$PS_ID}</title>";
?>
	<style>
		@media print {
			@page {
				size: portrait;
			}
		}

		body, td {
			font-family: Trebuchet MS, Tahoma, Verdana, Arial, sans-serif;
			font-size: 10pt;
		}
		table {
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
        @media print {
            .page {
                page-break-after: always;
            } 
        } 
	</style>
</head>
<body>

<?php
    // Получаем дату планируемой отгрузки
    $query = "
        SET lc_time_names = 'ru_RU';
    ";
    mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
    $query = "
        SELECT DATE_FORMAT(PS.ps_date, '%e %M %Y') ps_date_format
            ,IFNULL(PS.prior, (SELECT IFNULL(MAX(prior), 0) FROM plan__Shipment WHERE F_ID = PS.F_ID AND ps_date = PS.ps_date AND shipment_time IS NOT NULL)
            + (SELECT IFNULL(SUM(1), 0) FROM plan__Shipment WHERE PS_ID <= PS.PS_ID AND F_ID = PS.F_ID AND ps_date = PS.ps_date AND shipment_time IS NULL)) priority
            ,F.job_title_1
            ,F.full_name_1
        FROM plan__Shipment PS
        JOIN factory F ON F.F_ID = PS.F_ID
        WHERE PS.PS_ID = {$PS_ID}
    ";
    $res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
    $ps_date_format = $row["ps_date_format"];
    $priority = $row["priority"];
    $job_title_1 = $row["job_title_1"];
    $full_name_1 = $row["full_name_1"];

    // Цикл по списку грузоотправителей
    $query = "
        SELECT M.M_ID
            ,M.company
        FROM plan__ShipmentCWP PSC
        JOIN CounterWeightPallet CWP ON CWP.CWP_ID = PSC.CWP_ID
        JOIN Manufacturer M ON M.M_ID = CWP.M_ID
        WHERE PSC.PS_ID = {$PS_ID}
        GROUP BY M.M_ID
    ";
    $res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
    while( $row = mysqli_fetch_array($res) ) {
        $M_company = $row["company"];
        $M_ID = $row["M_ID"];

        // Узнаем грузополучателя
        $query = "
            SELECT CB.company
                ,CB.CB_ID
            FROM plan__ShipmentCWP PSC
            JOIN CounterWeightPallet CWP ON CWP.CWP_ID = PSC.CWP_ID
                AND CWP.M_ID = {$M_ID}
            JOIN ClientBrand CB ON CB.CB_ID = CWP.CB_ID
            WHERE PSC.PS_ID = {$PS_ID}
            LIMIT 1
        ";
        $subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
        $subrow = mysqli_fetch_array($subres);
        $CB_company = $subrow["company"];
        $CB_ID = $subrow["CB_ID"];
 
        // Статические данные
        $statics = "
            <p style='font-size: 1.1em; float: left; margin: 0px;'>Грузоотправитель: <span style='text-decoration: underline;'>{$M_company}</span></p>\n
            <p style='text-align: right;'>от {$ps_date_format} г.</p>\n
            <p style='text-align: center; font-size: 1.3em;'><b style='text-decoration: underline;'>Накладная №{$PS_ID} / {$priority}</b></p>\n
            <!--<p style='font-size: 1.1em;'>Грузоотправитель: <span style='text-decoration: underline;'>{$M_company}</span></p>\n-->
            <table>\n
                <thead>\n
                    <tr>\n
                        <th>№ п-п</th>\n
                        <th>Наименование</th>\n
                        <th>Ед. изм.</th>\n
                        <th>Кол-во</th>\n
                        <th>Ед. изм.</th>\n
                        <th>Кол-во</th>\n
                    </tr>\n
                </thead>\n
                <tbody style='text-align: center;'>\n
        ";

        $query = "
            SELECT IFNULL(CW.drawing_item, CWP.cwp_name) item
                ,PSC.quantity
                ,PSC.quantity * CWP.in_pallet amount
            FROM plan__ShipmentCWP PSC
            JOIN CounterWeightPallet CWP ON CWP.CWP_ID = PSC.CWP_ID
                AND CWP.M_ID = {$M_ID}
            LEFT JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
            WHERE PSC.PS_ID = {$PS_ID}
        ";
        $subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
        $i = 1;
        $quantity = 0;
        while( $subrow = mysqli_fetch_array($subres) ) {
            $statics .= "
                <tr>
                    <td>{$i}</td>\n
                    <td>{$subrow["item"]}</td>\n
                    <td>упаковка</td>\n
                    <td>{$subrow["quantity"]}</td>\n
                    <td>шт.</td>\n
                    <td>{$subrow["amount"]}</td>\n
                </tr>\n
            ";
            $i++;
            $quantity += $subrow["quantity"];
        }

        // Дополнение пустыми строками  
        for( $i; $i<=8; $i++ ) {
            $statics .= "
                <tr>
                    <td>{$i}</td>\n
                    <td></td>\n
                    <td></td>\n
                    <td></td>\n
                    <td></td>\n
                    <td></td>\n
                </tr>\n
            ";
        }
    
        $statics .= "
                </tbody>\n
            </table>\n
            <div style='margin-top: 20px; display: flex; justify-content: space-between; flex-wrap: wrap;'>\n
                <div style='width: 47%;'>\n
                    <p style='margin-bottom: 0px;'>Сдал: _______________ / _______________</p>\n
                    <p style='margin-top: 0px;'><sup style='margin-left: 75px;'>подпись</sup><sup style='margin-left: 85px;'>Ф.И.О.</sup></p>\n
                </div>\n
                <div style='width: 47%;'>\n
                    <p style='margin-bottom: 0px;'>Принял: _______________ / _______________</p>\n
                    <p style='margin-top: 0px;'><sup style='margin-left: 90px;'>подпись</sup><sup style='margin-left: 85px;'>Ф.И.О.</sup></p>\n
                </div>\n
            </div>\n
        ";

        // Формируем бланк
        echo "
            <div class='page'>\n
                <div style='border-bottom: 1px dotted; position: relative; height: 333px;'>\n
                    <div style='position: absolute; top: 20px; border: 2px solid; padding: 0 20px; height: 33px; text-align: center; font-weight: bold;'>ЭКЗЕМПЛЯР<br>охраны</div>\n
                    {$statics}
                </div>\n
                <div style='border-bottom: 1px dotted; position: relative; height: 333px;'>\n
                    <div style='position: absolute; top: 20px; border: 2px solid; padding: 0 20px; height: 33px; text-align: center; font-weight: bold;'>ЭКЗЕМПЛЯР<br>{$CB_company}</div>\n
                    {$statics}
                </div>\n
                <div style='position: relative; height: 333px;'>\n
                    <div style='position: absolute; top: 20px; border: 2px solid; padding: 0 20px; height: 33px; text-align: center; font-weight: bold;'>ЭКЗЕМПЛЯР<br>водителя</div>\n
                    {$statics}
                </div>\n
            </div>\n
        ";

        // Накладная на паллеты BEKO
        if( $CB_ID == 3 ) {
            echo "
                <div class='page'>
                    <div style='border-bottom: 2px solid; width: 100%;'>
                        <p style='text-align: left; font-size: 1.3em;'><b>Расходная накладная №{$PS_ID} от {$ps_date_format} г.</b></p>
                    </div>
                    <p style='text-align: left;'>Поставщик: <b>{$M_company}</b></p>
                    <p style='text-align: left;'>Покупатель: <b>{$CB_company}</b></p>
                    <table style='border: 2px solid;'>
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>Артикул</th>
                                <th>Товар</th>
                                <th colspan='2'>Мест</th>
                                <th colspan='2'>Количество</th>
                                <th>Цена</th>
                                <th>Сумма</th>
                            </tr>
                        </thead>
                        <tbody style='text-align: left;'>
                            <tr>
                                <td style='text-align: center;'>1</td>
                                <td></td>
                                <td>Паллет для противовесов 4698020079</td>
                                <td style='width: 30px;'></td>
                                <td>шт</td>
                                <td style='text-align: right;'>{$quantity}</td>
                                <td>шт</td>
                                <td style='text-align: right;'>0,00</td>
                                <td style='text-align: right;'>0,00</td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                    <div style='margin-top: 0px; display: flex; justify-content: space-between; flex-wrap: wrap;'>
                        <p style='display: block; text-align: right; width: 90%;'><b>Итого:</b></p>
                        <p style='display: block; text-align: right; width: 9%;'><b>0,00</b></p>
                    </div>
                    <div style='margin-top: 0px; display: flex; justify-content: space-between; flex-wrap: wrap;'>
                        <p style='display: block; text-align: right; width: 90%;'><b>Сумма НДС</b></p>
                        <p style='display: block; text-align: right; width: 9%;'><b>0,00</b></p>
                    </div>
                    <div style='margin-top: 0px; border-bottom: 2px solid;'>
                        <p>Всего наименований 1 на сумму 0,00 руб.</p>
                    </div>
                <div style='margin-top: 20px; display: flex; justify-content: space-between; flex-wrap: wrap;'>
                    <div style='width: 47%;'>
                        <div style='margin-bottom: 0px; display: flex; justify-content: space-between; flex-wrap: wrap;'>
                            <div style='width: 49%;'><p><b>Отпустил</b> ___________ </p></div>
                            <div style='text-decoration: underline; width: 49%;'><p style='font-size: 0.9em; margin: 0px;'>{$job_title_1} {$full_name_1}</p></div>
                        </div>
                    </div>
                    <div style='width: 47%;'>
                        <p style='margin-bottom: 0px;'><b>Получил</b> _______________________ </p>
                    </div>
                </div>

                </div>
            ";
        }
    }
?>
</html>
