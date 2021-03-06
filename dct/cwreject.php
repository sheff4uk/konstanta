<?
include_once "../config.php";

//Изменение статуса противовеса
if( isset($_POST["WT_ID"]) ) {
	$query = "
		UPDATE list__Weight
		SET goodsID = {$_POST["goodsID"]}
		WHERE WT_ID = {$_POST["WT_ID"]} AND nextID = {$_POST["nextID"]}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	exit ('<meta http-equiv="refresh" content="0; url=/dct/cwreject.php?WT_ID='.$_POST["WT_ID"].'&nextID='.$_POST["nextID"].'">');
}
?>

<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Статус противовеса</title>
		<script src="../js/jquery-1.11.3.min.js"></script>
		<script>
			$(function() {
				// Считывание штрихкода
				var barcode="";
				$(document).keydown(function(e)
				{
					var code = (e.keyCode ? e.keyCode : e.which);
					if (code==0) barcode="";
					if( code==13 || code==9 )// Enter key hit. Tab key hit.
					{
						var WT_ID = Number(barcode.substr(0, 8)),
							nextID = Number(barcode.substr(8, 8));
						$(location).attr('href','/dct/cwreject.php?WT_ID='+WT_ID+'&nextID='+nextID);
						barcode="";
						return false;
					}
					else
					{
						if (code >= 48 && code <= 57) {
							barcode = barcode + String.fromCharCode(code);
						}
					}
				});
			});
		</script>
	</head>
	<body>
		<h3>Отсканируйте противовес</h3>
		<?
		if( isset($_GET["WT_ID"]) ) {
			$query = "
				SELECT LW.weight
					,DATE_FORMAT(LW.weighing_time, '%d.%m.%Y %h:%i') weighing_time_format
					,LW.goodsID
					,LO.cassette
					,DATE_FORMAT(LF.lf_date, '%d.%m.%Y') lf_date_format
					,DATE_FORMAT(LF.lf_time, '%h:%i') lf_time_format
				FROM list__Weight LW
				JOIN list__Opening LO ON LO.LO_ID = LW.LO_ID
				JOIN list__Filling LF ON LF.LF_ID = LO.LF_ID
				WHERE LW.WT_ID = {$_GET["WT_ID"]} AND LW.nextID = {$_GET["nextID"]}
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);

			$WT_ID = str_pad($_GET["WT_ID"], 8, "0", STR_PAD_LEFT);
			$nextID = str_pad($_GET["nextID"], 8, "0", STR_PAD_LEFT);
			echo "<h1 style='text-align: center;'>{$WT_ID}{$nextID}</h1>";

			//Форма изменения статуса противовеса
			?>
			<fieldset>
				<legend>Статус противовеса</legend>
				<form method="post" style="font-size: 2em;">
					<input type="hidden" name="WT_ID" value="<?=$_GET["WT_ID"]?>">
					<input type="hidden" name="nextID" value="<?=$_GET["nextID"]?>">
					<select name="goodsID" onchange="this.form.submit()" style="font-size: 1em;">
						<option value="1">OK</option>
						<option value="2">Непролив</option>
						<option value="3">Мех. трещина</option>
						<option value="4">Усад. трещина</option>
						<option value="5">Скол</option>
						<option value="6">Дефект формы</option>
						<option value="7">Дефект сборки</option>
					</select>
				</form>
			</fieldset>
			<script>
				$(function() {
					$('select[name="goodsID"]').val(<?=$row["goodsID"]?>);
				});
			</script>
			<?

			echo "Дата/время заливки: <b>{$row["lf_date_format"]} {$row["lf_time_format"]}</b><br>";
			echo "Кассета: <b>{$row["cassette"]}</b><br>";
			echo "Вес: <b>{$row["weight"]}</b> г.<br>";
			echo "Дата/время регистрации: <b>{$row["weighing_time_format"]}</b><br>";
		}
		?>
	</body>
</html>
