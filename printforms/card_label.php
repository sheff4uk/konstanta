<?php
include "../config.php";
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<!-- <script src="https://kit.fontawesome.com/020f21ae61.js" crossorigin="anonymous"></script> -->
	<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> -->
		<link rel="stylesheet" type='text/css' href="../assets/fontawesome/css/all.min.css">

	<style>
		body {
			margin: 0;
			padding: 0;
			font: 10pt "Arial";
			display: flex;
			flex-wrap: wrap;
		}
		* {
			box-sizing: border-box;
			-moz-box-sizing: border-box;
		}
		.box {
			position: relative;
			overflow:hidden;
			width:72mm;
			height:54mm;
			border: 1px solid;
			-webkit-print-color-adjust: exact;
			print-color-adjust: exact;
		}
		.box img {
			position: absolute;
			top:50%;
			left:50%;
			transform:translate(-50%,-50%);
			width:72mm;
			height:54mm;
			object-fit:cover;
			font-size: 8mm;
			word-wrap: break-word;
		}
		.box span {
			filter: drop-shadow(0px 0px 2px #000) drop-shadow(0px 0px 2px #000) drop-shadow(0px 0px 2px #000);
			color: #fff;
			font-size: 8mm;
			margin: 2mm;
			position: absolute;
			bottom: 0mm;
			word-wrap: break-word;
			line-height: 1em;
		}
	</style>
</head>
<body>
<?php
	$query = "
		SELECT USR_Name(USR_ID) name
			,USR_Icon(USR_ID) icon
			,photo
		FROM Users
		WHERE USR_ID = {$_GET["USR_ID"]}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	//$row = mysqli_fetch_array($res);
	while( $row = mysqli_fetch_array($res) ) {
?>
	<div class="box">
		<img src="/time_tracking/upload/<?=$row["photo"]?>">
		<span><?=$row["name"]?></span>
		<div style="position: absolute; top: 15px; left: 10px; transform: scale(1.5);"><?=$row["icon"]?></div>
	</div>
<?php
	}
?>
</body>
</html>
