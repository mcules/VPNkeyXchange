<?php

require "function.php";

$hoodfilemode = isset($_GET['hoodfile']);

$polydata = getPolyhoodsByHood(); // read polygon data for later use

try {
	$q = 'SELECT ID, name, net, lat, lon, ESSID_AP, active FROM hoods WHERE active=1;';
	$rs = db::getInstance()->prepare($q);
	$rs->execute();
} catch (PDOException $e) {
	exit(showError(500, $e));
}

$hoods = array();
while ( $result = $rs->fetch ( PDO::FETCH_ASSOC ) ) {
	$hood = array();
	$hood['id'] = intval($result['ID']);
	$hood['name'] = $result['name'];
	$hood['net'] = $result['net'];
	$hood['essid_ap'] = $result['ESSID_AP'];
	$hood['active'] = $result['active'];
	if ($result ['lat'] > 0 && $result ['lon'] > 0) {
		$hood['lat'] = floatval($result['lat']);
		$hood['lon'] = floatval($result['lon']);
	}

	if (isset($polydata[$result['ID']])) {
		$hood['polygons'] = array_values($polydata[$result['ID']]); // we don't need the polyids here
	}

	if (!$hoodfilemode) {
		array_push($hoods, $hood);
	} else {
		$ispoly = false;
		if (isset($hood['polygons'])) {
			$sumlat = 0;
			$sumlon = 0;
			// calculate average coordinates of first polygon; this may give wrong coordinates, but this is relatively unlikely
			foreach ($hood['polygons'][0] as $poly) {
				$sumlat += $poly['lat'];
				$sumlon += $poly['lon'];
			}
			$result['lat'] = $sumlat / count($hood['polygons'][0]);
			$result['lon'] = $sumlon / count($hood['polygons'][0]);
			$ispoly = true;
		}
		echo 'ID: '.$result['ID'].' ; Name: '.$result['name'].' ; Net: '.$result['net'].' ; lat: '.$result['lat'].' ; lon: '.$result['lon'].' ; type: '.($ispoly ? 'poly' : 'classic').' ; <a href="http://keyserver.freifunk-franken.de/v2/?lat='.$result['lat'].'&long='.$result['lon'].'">zum Hoodfile</a><br>';
	}
}

if($hoodfilemode) { exit(); }
header("Content-Type: application/json");
echo json_encode($hoods, JSON_PRETTY_PRINT);

?>
