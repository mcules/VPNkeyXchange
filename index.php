<?php
/**
 * Handle VPN-Key-Exchange request for Freifunk-Franken.
 *
 * @author RedDog <reddog@mastersword.de>
 * @author delphiN <freifunk@wunschik.net>
 * @author Mose <mose@fabfolk.com>
 * @author Christian Dresel <fff@chrisi01.de>
 * @author Dennis Eisold <fff@itstall.de>
 * @author Adrian Schmutzler <freifunk@adrianschmutzler.de>
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

require "function.php";

const DEBUG = false;

$hood = array();

if (isset($_GET['lat']) && $_GET['lat'] !== "" && isset($_GET['long']) && $_GET['long'] !== "" && is_numeric($_GET['lat']) && is_numeric($_GET['long'])) {
	$lat = $_GET['lat'];
	$lon = $_GET['long'];
	$point = array($lon,$lat); // coordinates of router

	// Zuerst nach geojson hood pruefen
	$pointLocation = new pointLocation();

	// First only retrieve list of polyids
	try {
		$rc = db::getInstance()->prepare("SELECT polyid, hoodid, lat, lon FROM polyhood");
		$rc->execute();
	} catch (PDOException $e) {
		exit(showError(500, $e));
	}

	// Write polygon data into array
	$polystore = array();
	while($row = $rc->fetch(PDO::FETCH_ASSOC)) {
		if(!isset($polystore[$row['polyid'])) {
			$polystore[$row['polyid']] = array('hoodid'=>$row['hoodid'],'data'=>array());
		}
		$polystore[$row['polyid']]['data'][] = array($row["lon"],$row["lat"]);
		debug('lon: '.$row["lon"].' lat: '.$row["lat"]);
	}

	// Interpret polygon data
	foreach($polystore as $polyid => $polygon) {
		$inside = $pointLocation->pointInPolygon($point, $polygon['data']);
		debug("point in polygon #" . $polyid. ": " . $inside . "<br>");
		if ($inside) {
			debug("PolyHood gefunden...");
			try {
				$rs = db::getInstance()->prepare("SELECT ".hood_mysql_fields." FROM hoods WHERE id=:hoodid;");
				$rs->bindParam(':hoodid', $polygon['hoodid'], PDO::PARAM_INT);
				$rs->execute();
			} catch (PDOException $e) {
				exit(showError(500, $e));
			}
			$hood = $rs->fetch(PDO::FETCH_ASSOC);
			break;
		}
	}

	// danach voronoi wenn keine PolyHood gefunden wurde
	if (empty($hood)) {
		debug("Searching a hood on " . $lat . " " . $lon . ":");
		$hood = getHoodByGeo($lat, $lon);
		if (!empty($hood)) {
			debug($hood);
		}
	}
}

if (empty($hood)) { 
	debug("No hood found, using Trainstaion:");
	$hood = getTrainstation();
	debug($hood);
}

$json = array();
$json['version'] = 1;
$json['network'] = array('ula_prefix' => $hood['prefix']);
$json['vpn'] = getAllVPNs($hood['ID']);
unset($hood['ID']);
unset($hood['prefix']);

unset($hood['lat']);
unset($hood['lon']);

$json['hood'] = $hood;

echo json_encode($json);

// vim: expandtab:sw=2:ts=2
?>
