<?
include_once "../checkrights.php";

$LF_ID = $_GET["LF_ID"];
$type = $_GET["type"]; // 1-расформовка, 2-упаковка

$filling_select = "<option value=\"\"></option>";

if( $type == 1 ) {
	$query = "
		SELECT LF.LF_ID
			,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date
			,LF.cassette
		FROM list__Batch LB
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		LEFT JOIN list__Opening LO ON LO.LF_ID = LF.LF_ID
		WHERE LO.LO_ID IS NULL
		".($LF_ID ? "OR LF.LF_ID = {$LF_ID}" : "")."
		ORDER BY LB.batch_date, LF.cassette
	";
}

if( $type == 2 ) {
	$query = "
		SELECT LF.LF_ID
			,DATE_FORMAT(LB.batch_date, '%d.%m.%y') batch_date
			,LF.cassette
		FROM list__Batch LB
		JOIN list__Filling LF ON LF.LB_ID = LB.LB_ID
		LEFT JOIN list__Packing LP ON LP.LF_ID = LF.LF_ID
		WHERE LP.LP_ID IS NULL
		".($LF_ID ? "OR LF.LF_ID = {$LF_ID}" : "")."
		ORDER BY LB.batch_date, LF.cassette
	";
}

$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$filling_select .= "<option value=\"{$row["LF_ID"]}\">{$row["batch_date"]} кассета №{$row["cassette"]}</option>";
}

echo "$('#filling_select').html('{$filling_select}');";
?>