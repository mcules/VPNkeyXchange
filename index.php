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
	$polyon = (isset($_GET['poly']) && $_GET['poly']==0) ? false : true; // default on, disable with poly=0

	// Zuerst nach geojson hood pruefen
	if($polyon) {
		$hood = processPoly($point);
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
