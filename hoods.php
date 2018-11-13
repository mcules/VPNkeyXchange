<?php

require "function.php";

$polydata = getPolyhoodsByHood(); // read polygon data for later use

try {
	$q = 'SELECT ID, name, net, lat, lon FROM hoods;';
	$rs = db::getInstance()->prepare($q);
	$rs->execute();
} catch (PDOException $e) {
	exit(showError(500, $e));
}

$hoods = array();
while ( $result = $rs->fetch ( PDO::FETCH_ASSOC ) ) {
	$hood = array();
	$hood['id']   = intval($result['ID']);
	$hood['name'] = $result['name'];
	$hood['net']  = $result['net'];
	$hood['essid_ap'] = $result['ESSID_AP'];
	if ($result ['lat'] > 0 && $result ['lon'] > 0) {
		$hood['lat'] = floatval($result['lat']);
		$hood['lon'] = floatval($result['lon']);
	}
	if(isset($polydata[$result['ID']])) {
		$hood['polygons'] = array_values($polydata[$result['ID']]); // we don't need the polyids here
	}
	array_push($hoods, $hood);
}

header("Content-Type: application/json");
echo json_encode($hoods, JSON_PRETTY_PRINT);

?>
