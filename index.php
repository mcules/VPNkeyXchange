<?php
/**
 * Handle VPN-Key-Exchange request for Freifunk-Franken.
 *
 * @author RedDog <reddog@mastersword.de>
 * @author delphiN <freifunk@wunschik.net>
 * @author Mose <mose@fabfolk.com>
 * @author Christian Dresel <fff@chrisi01.de>
 * @author Dennis Eisold <fff@itstall.de>
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */

require "function.php";

const DEBUG = false;

$hood = array();

if (isset($_GET['lat']) && $_GET['lat'] !== "" && isset($_GET['long']) && $_GET['long'] !== "" && is_numeric($_GET['lat']) && is_numeric($_GET['long'])) {
	$lat = $_GET['lat'];
	$lon = $_GET['long'];
	
	#zuerst nach geojson hood prüfen 
	$pointLocation = new pointLocation();
	#zuerst Anzal Polyhoods zählen:
	try {
		$rc = db::getInstance()->prepare("SELECT DISTINCT polyid FROM polyhood");
		$rc->execute();
	} catch (PDOException $e) {
		exit(showError(500, $e));
	}
	$result = $rc->fetchAll();
	// Abfrage der Polygone ob eins passt
	foreach($result as $row) {
		try {
			$rs = db::getInstance()->prepare("SELECT * FROM polyhood WHERE polyid=:polyid");
			$rs->bindParam(':polyid', $row['polyid']);
			$rs->execute();
		} catch (PDOException $e) {
			exit(showError(500, $e));
		}
		$polygon = array();
		// return results in a easy parsable way
		while ($result = $rs->fetch(PDO::FETCH_ASSOC)) {
			$polygeo = ''.$result["lon"].' '.$result["lat"].'';
			debug($polygeo);
			array_push($polygon, $polygeo);
			$hoodid = $result['hoodid'];
		}
		$point = "$lon $lat";
		debug("point " . ($key + 1) . " ($point): " . $pointLocation->pointInPolygon($point, $polygon) . "<br>");
		if ($pointLocation->pointInPolygon($point, $polygon)) {
			debug("PolyHood gefunden...");
			$found = 1;
			try {
				$rs = db::getInstance()->prepare("SELECT ".hood_mysql_fields." FROM hoods WHERE id=:hoodid;");
				$rs->bindParam(':hoodid', $hoodid, PDO::PARAM_INT);
				$rs->execute();
			} catch (PDOException $e) {
				exit(showError(500, $e));
			}
			$hood = $rs->fetch(PDO::FETCH_ASSOC);
			break;
		}
	}
	// danach voronoi wenn keine PolyHood gefunden wurde
	if (!$found) {
		debug("Searching a hood on " . $lat . " " . $lon . ":");
		$hood = getHoodByGeo($lat, $lon);
		$hoodid = $hood['ID'];
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

$hood['location'] = array('lat' => $hood['lat'], 'lon' => $hood['lon']);
unset($hood['lat']);
unset($hood['lon']);

unset($hood['ID']);
unset($hood['prefix']);
$json['hood'] = $hood;

echo json_encode($json);

// vim: expandtab:sw=2:ts=2
?>
