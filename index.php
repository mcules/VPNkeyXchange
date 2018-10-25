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

function debug($msg)
{
    if (DEBUG) {
        print_r($msg);
        echo "\n";
    }
}

/**
 * Singelton DB instance
 */
class db
{
    private static $instance = NULL;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            require('config.inc.php');
            self::$instance = new PDO('mysql:host=' . $mysql_server . ';dbname=' . $mysql_db, $mysql_user, $mysql_pass);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$instance;
    }

    private function __clone()
    {
    }
}

/**
 * returns details error msg (as json)
 *
 * @param integer $code HTTP error 400, 500 or 503
 * @param string $msg Error message text
 */
function showError($code, $msg)
{
    if ($code == 400) {
        header('HTTP/1.0 400 Bad Request');
    } elseif ($code == 500) {
        header('HTTP/1.0 500 Internal Server Error');
    } elseif ($code == 503) {
        header('HTTP/1.0 503 Service Unavailable');
    }

    header('Content-Type: application/json');

    $errorObject = array('error' => array('msg' => $msg, 'url' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']));
    print_r(json_encode($errorObject));
}

function sin_d($value)
{
    return sin(deg2rad($value));
}

function cos_d($value)
{
    return cos(deg2rad($value));
}

const EARTH_RADIUS = 6371;
/**
 * Haversine distance function in km
 * https://en.wikipedia.org/wiki/Haversine_formula
 *
 * @param double $lat1 latitude point 1
 * @param double $lon1 longitude point 1
 * @param double $lat2 latitude point 2
 * @param double $lon2 longitude point 2
 * @return integer distance between the points in km
 */
function distance_haversine($lat1, $lon1, $lat2, $lon2)
{
    $delta_lat = $lat1 - $lat2;
    $delta_lon = $lon1 - $lon2;
    $alpha = $delta_lat / 2;
    $sin_alpha_2 = sin_d($alpha) * sin_d($alpha);
    $beta = $delta_lon / 2;
    $sin_beta_2 = sin_d($beta) * sin_d($beta);
    $a = $sin_alpha_2 + cos_d($lat1) * cos_d($lat2) * $sin_beta_2;
    $c = asin(min(1, sqrt($a)));
    $distance = 2 * EARTH_RADIUS * $c;
    $distance = round($distance, 3);
    return $distance;
}

/**
 * Check is the given geo coordinates are within one of the hoods.
 *
 * @param double $lat latitude point 1
 * @param double $lon longitude point 1
 * @return integer hood-id
 */
function getHoodByGeo($lat, $lon)
{
    $current_hood_dist = 99999999;
    $best_result = array();

    // load hoods from DB
    try {
        $q = 'SELECT ' . hood_mysql_fields . ' FROM hoods;';
        $rs = db::getInstance()->prepare($q);
        $rs->execute();
    } catch (PDOException $e) {
        exit(showError(500, $e));
    }

    // check for every hood if it's nearer than the hood before
    while ($result = $rs->fetch(PDO::FETCH_ASSOC)) {
        debug("\n\nhood: " . $result['name']);

        if (is_null($result['lat']) || is_null($result['lon'])) {
            continue;
        }

        debug('hoodCenterLat: ' . $result['lat'] . ', hoodCenterLon: ' . $result['lon'] . ', hoodID: ' . $result['ID']);

        $distance = distance_haversine($result['lat'], $result['lon'], $lat, $lon);
        debug('distance: $distance');

        if ($distance <= $current_hood_dist) {
            debug('Node belongs to Hood ' . $result['ID'] . '(' . $result['name'] . ')');
            $current_hood_dist = $distance;
            $best_result = $result;
        }
    }

    return $best_result;
}

function getTrainstation()
{
    try {
        $q = 'SELECT ' . hood_mysql_fields . ' FROM hoods WHERE ID="0";';
        $rs = db::getInstance()->prepare($q);
        $rs->execute();
    } catch (PDOException $e) {
        exit(showError(500, $e));
    }

    return $rs->fetch(PDO::FETCH_ASSOC);
}

function getAllVPNs($hoodId)
{
    $ret = array();

    // return either all all gateways from the hood
    try {
        $sql = 'SELECT g.name, "fastd" AS protocol, g.ip AS address, g.port, g.key
            FROM gateways AS g WHERE hood_ID=:hood;';
        $rs = db::getInstance()->prepare($sql);
        $rs->bindParam(':hood', $hoodId);
        $rs->execute();
    } catch (PDOException $e) {
        exit(showError(500, $e));
    }
    while ($result = $rs->fetch(PDO::FETCH_ASSOC)) {
        array_push($ret, $result);
    }
    return $ret;
}

function getPolyhoods()
{
	try {
        $rs = db::getInstance()->query("SELECT * FROM polyhood;");
        $rs->execute();
    } catch (PDOException $e) {
        exit(showError(500, $e));
    }
	$result = $rs->fetchall(PDO::FETCH_ASSOC);
	foreach($result as $row) {
		$return[$row['hoodid']][] = array('lat' => $row['lat'], 'lon' => $row['lon']);
	}
	return $return;
}

$hood = array();
if (isset($_GET['lat']) && $_GET['lat'] !== "" && isset($_GET['long']) && $_GET['long'] !== "") {
    $lat = $_GET['lat'];
    $lon = $_GET['long'];
    if (!is_numeric($lat) OR !is_numeric($lon)) {
        echo "nix sqlinject";
        exit;
    }
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
                debug("r");
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

if (empty($hood) && found != 0) {
    debug("No hood found, using Trainstaion:");
    $hood = getTrainstation();
    debug($hood);
}

$json = array();
if ($_REQUEST['action'] == "visualize") {
	$json = getPolyhoods();
} else {
    $json['version'] = 1;
    $json['network'] = array('ula_prefix' => $hood['prefix']);
    $json['vpn'] = getAllVPNs($hood['ID']);

    $hood['location'] = array('lat' => $hood['lat'], 'lon' => $hood['lon']);
    unset($hood['lat']);
    unset($hood['lon']);

    unset($hood['ID']);
    unset($hood['prefix']);
    $json['hood'] = $hood;
}
echo json_encode($json);

// vim: expandtab:sw=2:ts=2
?>
