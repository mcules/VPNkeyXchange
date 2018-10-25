<?php
/**
 * Handle VPN-Key-Exchange request for Freifunk-Franken.
 *
 * @author RedDog <reddog@mastersword.de>
 * @author delphiN <freifunk@wunschik.net>
 * @author Mose <mose@fabfolk.com>
 * @author Christian Dresel <fff@chrisi01.de>
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */


include("function.php");

const DEBUG = false;

const hood_mysql_fields = 'ID,
name,
ESSID_AP as essid,
BSSID_MESH as mesh_bssid,
ESSID_MESH as mesh_essid,
mesh_id,
protocol,
channel2,
mode2,
mesh_type2,
channel5,
mode5,
mesh_type5,
upgrade_path,
ntp_ip,
UNIX_TIMESTAMP(changedOn) as timestamp,
prefix, lat, lon';

$lat = $_GET['lat'];
$lon = $_GET['long'];
$hood = array();
if (isset($_GET['lat']) && $_GET['lat'] !== "" && isset($_GET['long']) && $_GET['long'] !== "" && is_numeric($lat) && is_numeric($lon)) {
    #zuerst nach geojson hood prüfen 
    $pointLocation = new pointLocation();
    #zuerst Anzal Polyhoods zählen:
    try {
        $sql = 'SELECT DISTINCT polyid FROM polyhood';
        $rs = db::getInstance()->prepare($sql);
        $rs->execute();
    } catch (PDOException $e) {
        exit(showError(500, $e));
    }
    $polyhoodmenge = $rs->rowCount();
    #Abfrage der Polygone ob eins passt
    debug($polyhoodmenge);
    $i = 1;
    while ($i <= $polyhoodmenge AND $found == 0) {
        try {
            $sql = 'SELECT * FROM polyhood WHERE polyid=' . $i . '';
            $rs = db::getInstance()->prepare($sql);
            $rs->execute();
        } catch (PDOException $e) {
            exit(showError(500, $e));
        }
        $polygon = array();
        // return results in a easy parsable way
        if ($rs->rowCount() > 0) {
            while ($result = $rs->fetch(PDO::FETCH_ASSOC)) {
                $polygeo = ''.$result["lon"].' '.$result["lat"].'';
                debug($polygeo);
                array_push($polygon, $polygeo);
                $hoodid = $result['hoodid'];
            }
        }
        //foreach($points as $key => $point) {
        $point = "$lon $lat";
        debug("point " . ($key + 1) . " ($point): " . $pointLocation->pointInPolygon($point, $polygon) . "<br>");
        if ($pointLocation->pointInPolygon($point, $polygon) == 1) {
            debug("PolyHood gefunden...");
            $found = 1;
            try {
                $q = "SELECT " . hood_mysql_fields . " FROM hoods WHERE id=" . $hoodid . ";";
                $rs = db::getInstance()->prepare($q);
                $rs->execute();
            } catch (PDOException $e) {
                exit(showError(500, $e));
            }
            $hood = $rs->fetch(PDO::FETCH_ASSOC);
        }
        $i++;
    }
    #danach voronoi wenn keine PolyHood gefunden wurde
    if ($found != 1) {
        debug("Searching a hood on " . $_GET['lat'] . " " . $_GET['long'] . ":");
        $hood = getHoodByGeo($_GET['lat'], $_GET['long']);
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
