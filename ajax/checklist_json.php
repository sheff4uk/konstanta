<?
include_once "../checkrights.php";

$LB_ID = $_GET["LB_ID"];

$query = "
	SELECT LB.CW_ID
		,LB.OP_ID
		,LB.batch_date
		,LB.batch_time
		,LB.comp_density/1000 comp_density
		,LB.mix_density/1000 mix_density
		,LB.iron_oxide
		,LB.sand
		,LB.crushed_stone
		,LB.cement
		,LB.water
		,LB.underfilling
	FROM list__Batch LB
	WHERE LB.LB_ID = {$LB_ID}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) )
{
	$comp_density = (float)$row["comp_density"];
	$mix_density = (float)$row["mix_density"];
	$LB_data = array( "CW_ID"=>$row["CW_ID"], "OP_ID"=>$row["OP_ID"], "batch_date"=>$row["batch_date"], "batch_time"=>$row["batch_time"], "comp_density"=>$comp_density, "mix_density"=>$mix_density, "iron_oxide"=>$row["iron_oxide"], "sand"=>$row["sand"], "crushed_stone"=>$row["crushed_stone"], "cement"=>$row["cement"], "water"=>$row["water"], "underfilling"=>$row["underfilling"] );
}

echo json_encode($LB_data);

?>