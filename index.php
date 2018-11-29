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

if (isset($_GET['hoodid']) && $_GET['hoodid']) {
	debug("Searching a hood on ID " . $_GET['hoodid'] . ":");
	$hood = getHoodById($_GET['hoodid']);
	if (!empty($hood)) {
		debug($hood);
	} else {
		exit(showError(400, "Hood not found"));
	}
} elseif(isset($_GET['lat']) && $_GET['lat'] !== "" && isset($_GET['long']) && $_GET['long'] !== "" && is_numeric($_GET['lat']) && is_numeric($_GET['long'])) {
	$lat = $_GET['lat'];
	$lon = $_GET['long'];
	$point = array($lon,$lat); // coordinates of router

	// Zuerst nach geojson hood pruefen
	$pointLocation = new pointLocation();

	// First only retrieve list of polyids
	try {
		$rc = db::getInstance()->prepare("
			SELECT polyhoods.polyid, hoodid, MIN(lat) AS minlat, MIN(lon) AS minlon, MAX(lat) AS maxlat, MAX(lon) AS maxlon
			FROM polyhoods INNER JOIN polygons ON polyhoods.polyid = polygons.polyid
			GROUP BY polyid, hoodid
		"); // This query will automatically exclude polyhoods being present in polyhoods table, but without vertices in polygons table
		$rc->execute();
	} catch (PDOException $e) {
		exit(showError(500, $e));
	}

	// Set up all polygons, but do it without vertex coordinates
	$polystore = array();
	while($row = $rc->fetch(PDO::FETCH_ASSOC)) {
		$polystore[$row['polyid']] = $row;
		$polystore[$row['polyid']]['data'] = array(); // prepare array for vertex coordinates
	}

	// Now query the coordinates, all in one query
	try {
		$rc = db::getInstance()->prepare("SELECT polyid, lat, lon FROM polygons ORDER BY ID ASC");
		$rc->execute();
	} catch (PDOException $e) {
		exit(showError(500, $e));
	}

	// Write polygon coordinates into array
	while($row = $rc->fetch(PDO::FETCH_ASSOC)) {
		if(!isset($polystore[$row['polyid']])) {
			debug('Database inconsistent: No polyhood defined for ID '.$row['polyid']);
			continue; // Skip those orphaned vertex entries
		}
		$polystore[$row['polyid']]['data'][] = array(floatval($row["lon"]),floatval($row["lat"]));
		debug('lon: '.$row["lon"].' lat: '.$row["lat"]);
	}

	// Interpret polygon data
	foreach($polystore as $polygon) {
		// First check whether point coordinates are outside the most extreme values for lat/lng
		$exclude = $pointLocation->excludePolygon($point, $polygon['minlon'], $polygon['maxlon'], $polygon['minlat'], $polygon['maxlat']);
		if ($exclude) {
			debug("polygon #" . $polygon['polyid'] . " excluded<br>");
			continue;
		}
		// Now really check whether point is inside polygon
		$polygon['data'][] = $polygon['data'][0]; // Add first point as last point (= close polygon)
		$inside = $pointLocation->pointInPolygon($point, $polygon['data']);
		debug("point in polygon #" . $polygon['polyid'] . ": " . $inside . "<br>");
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
$json['vpn'] = getAllVPNs($hood['id']);
unset($hood['prefix']);

unset($hood['lat']);
unset($hood['lon']);

$json['hood'] = $hood;

echo json_encode($json);

// vim: expandtab:sw=2:ts=2
?>
