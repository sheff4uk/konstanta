<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Терминал</title>
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
						//console.log(barcode);
						alert(barcode);
						if( barcode.length == 8 ) {
							//filling_form(Number(barcode));
							barcode="";
							return false;
						}
						barcode="";
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
		<h1>Терминал сбора данных</h1>
		<br>
	</body>
</html>