<?php

const hood_mysql_fields = '
	ID as id,
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
	prefix, lat, lon
';

class pointLocation {
// Original version: https://gist.github.com/jeremejazz/5219848
// Modified by Adrian Schmutzler, 2018.

    function excludePolygon($point, $minlon, $maxlon, $minlat, $maxlat) {
        // exclude polygon if LAT/LNG of point is smaller than minimum lat/lng of all vertices
        // or bigger than maximum ...

        // returning TRUE means exclusion, so polygon should NOT be used
        return ($point[0] < $minlon or $point[0] > $maxlon or $point[1] < $minlat or $point[1] > $maxlat);
    }

    function pointInPolygon($point, $polygon, $pointOnVertex = true) {

        // Support both string version "lng lat" and array(lng,lat)
        if(!is_array($point)) {
            $point = $this->pointStringToCoordinates($point);
        }

        $vertices = array();
        foreach ($polygon as $vertex) {
            if(is_array($vertex)) {
                $vertices[] = $vertex;
            } else {
                $vertices[] = $this->pointStringToCoordinates($vertex);
            }
        }

        // Check if the point sits exactly on a vertex
        if ($pointOnVertex and $this->pointOnVertex($point, $vertices)) {
            return false;
        }

        // Check if the point is inside the polygon or on the boundary
        $intersections = 0;

        for ($i=1; $i < count($vertices); $i++) {
            $vertex1 = $vertices[$i-1];
            $vertex2 = $vertices[$i];
            if ($vertex1[1] == $vertex2[1] and $vertex1[1] == $point[1]
                and $point[0] > min($vertex1[0], $vertex2[0]) and $point[0] < max($vertex1[0], $vertex2[0]))
            { // Check if point is on an horizontal polygon boundary
                return false;
            }
            if ($point[1] > min($vertex1[1], $vertex2[1]) and $point[1] <= max($vertex1[1], $vertex2[1])
                and $point[0] <= max($vertex1[0], $vertex2[0]) and $vertex1[1] != $vertex2[1])
            {
                $xinters = ($point[1] - $vertex1[1]) * ($vertex2[0] - $vertex1[0]) / ($vertex2[1] - $vertex1[1]) + $vertex1[0];
                if ($xinters == $point[0]) { // Check if point is on the polygon boundary (other than horizontal)
                    return false;
                }
                if ($vertex1[0] == $vertex2[0] || $point[0] <= $xinters) {
                    $intersections++;
                }
            }
        }
        // If the number of edges we passed through is odd, then it's in the polygon.
        return ($intersections % 2 != 0);
    }

    function pointOnVertex($point, $vertices) {
        foreach($vertices as $vertex) {
            if ($point == $vertex) { // works for arrays
                return true;
            }
        }
        return false;
    }

    function pointStringToCoordinates($pointString) {
        $coordinates = explode(" ", $pointString);
        return array($coordinates[0],$coordinates[1]);
    }

}

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
    http_response_code($code);

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
    $alpha = ($lat1 - $lat2) * 0.5;
    $beta = ($lon1 - $lon2) * 0.5;
    $sin_alpha = sin_d($alpha);
    $sin_beta = sin_d($beta);

    $a = $sin_alpha * $sin_alpha + cos_d($lat1) * cos_d($lat2) * $sin_beta * $sin_beta;
    $c = asin(min(1, sqrt($a)));
    $distance = 2 * EARTH_RADIUS * $c;

    return round($distance, 3);
}

/**
 * Check if the given geo coordinates are within one of the hoods.
 *
 * @param double $lat latitude point 1
 * @param double $lon longitude point 1
 * @return array hood data
 */
function getHoodByGeo($lat, $lon)
{
    $current_hood_dist = 99999999;
    $best_result = array();

    // load hoods from DB
    try {
        $q = 'SELECT '.hood_mysql_fields.' FROM hoods WHERE lat IS NOT NULL AND lon IS NOT NULL;';
        $rs = db::getInstance()->prepare($q);
        $rs->execute();
    } catch (PDOException $e) {
        exit(showError(500, $e));
    }

    // check for every hood if it's nearer than the hood before
    while ($result = $rs->fetch(PDO::FETCH_ASSOC)) {
        debug("\n\nhood: " . $result['name'] . ', CenterLat: ' . $result['lat'] . ', hoodCenterLon: ' . $result['lon'] . ', hoodID: ' . $result['id']);

        $distance = distance_haversine($result['lat'], $result['lon'], $lat, $lon);
        debug('distance: $distance');

        if ($distance <= $current_hood_dist) {
            debug('Shorter distance found for hood ' . $result['id'] . '(' . $result['name'] . ')');
            $current_hood_dist = $distance;
            $best_result = $result;
        }
    }

    return $best_result;
}

/**
 * Get hood data based on KeyXchange ID.
 *
 * @param string $hoodid hood ID
 * @return array hood data
 */
function getHoodById($hoodid)
{
    // load hood from DB
    try {
        $q = 'SELECT '.hood_mysql_fields.' FROM hoods WHERE ID = :hoodid;';
        $rs = db::getInstance()->prepare($q);
        $rs->bindParam(':hoodid', $hoodid);
        $rs->execute();
    } catch (PDOException $e) {
        exit(showError(500, $e));
    }
    return $rs->fetch(PDO::FETCH_ASSOC);
}

function getTrainstation()
{
    try {
        $q = 'SELECT '.hood_mysql_fields.' FROM hoods WHERE ID="0";';
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

    // return all gateways in the hood
    try {
        $sql = "SELECT g.name, 'fastd' AS protocol, g.ip AS address, g.port, g.publickey AS 'key'
            FROM gateways AS g WHERE hood_ID=:hood;";
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

function getPolyhoodsByHood()
{
    try {
        $rs = db::getInstance()->query("
            SELECT polyhoods.polyid, lat, lon, hoodid
            FROM polyhoods INNER JOIN polygons ON polyhoods.polyid = polygons.polyid
            ORDER BY hoodid ASC, polyid ASC, ID ASC;
        ");
        $rs->execute();
    } catch (PDOException $e) {
        exit(showError(500, $e));
    }
    $result = $rs->fetchall(PDO::FETCH_ASSOC);
    $return = array();
    foreach($result as $row) {
        // one array of polygons per hood
        if(!isset($return[$row['hoodid']])) {
            $return[$row['hoodid']] = array();
        }
        // one array of vertices per polygon
        if(!isset($return[$row['hoodid']][$row['polyid']])) {
            $return[$row['hoodid']][$row['polyid']] = array();
        }
        $return[$row['hoodid']][$row['polyid']][] = array('lat' => floatval($row['lat']), 'lon' => floatval($row['lon']));
    }
    return $return;
}

?>
