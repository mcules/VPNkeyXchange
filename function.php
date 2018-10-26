<?php

//Quelle: https://gist.github.com/jeremejazz/5219848
class pointLocation {
    var $pointOnVertex = true; // Check if the point sits exactly on one of the vertices?
 
    function pointLocation() {
    }
 
    function pointInPolygon($point, $polygon, $pointOnVertex = true) {
        $this->pointOnVertex = $pointOnVertex;
 
        // Transform string coordinates into arrays with x and y values
        $point = $this->pointStringToCoordinates($point);
        $vertices = array(); 
        foreach ($polygon as $vertex) {
            $vertices[] = $this->pointStringToCoordinates($vertex); 
        }
 
        // Check if the point sits exactly on a vertex
        if ($this->pointOnVertex == true and $this->pointOnVertex($point, $vertices) == true) {
            return false;
        }
 
        // Check if the point is inside the polygon or on the boundary
        $intersections = 0; 
        $vertices_count = count($vertices);
 
        for ($i=1; $i < $vertices_count; $i++) {
            $vertex1 = $vertices[$i-1]; 
            $vertex2 = $vertices[$i];
            if ($vertex1['y'] == $vertex2['y'] and $vertex1['y'] == $point['y'] and $point['x'] > min($vertex1['x'], $vertex2['x']) and $point['x'] < max($vertex1['x'], $vertex2['x'])) { // Check if point is on an horizontal polygon boundary
                return false;
            }
            if ($point['y'] > min($vertex1['y'], $vertex2['y']) and $point['y'] <= max($vertex1['y'], $vertex2['y']) and $point['x'] <= max($vertex1['x'], $vertex2['x']) and $vertex1['y'] != $vertex2['y']) { 
                $xinters = ($point['y'] - $vertex1['y']) * ($vertex2['x'] - $vertex1['x']) / ($vertex2['y'] - $vertex1['y']) + $vertex1['x']; 
                if ($xinters == $point['x']) { // Check if point is on the polygon boundary (other than horizontal)
                    return false;
                }
                if ($vertex1['x'] == $vertex2['x'] || $point['x'] <= $xinters) {
                    $intersections++; 
                }
            } 
        } 
        // If the number of edges we passed through is odd, then it's in the polygon. 
        if ($intersections % 2 != 0) {
            return true;
        } else {
            return false;
        }
    }
 
    function pointOnVertex($point, $vertices) {
        foreach($vertices as $vertex) {
            if ($point == $vertex) {
                return true;
            }
        }
 
    }
 
    function pointStringToCoordinates($pointString) {
        $coordinates = explode(" ", $pointString);
        return array("x" => $coordinates[0], "y" => $coordinates[1]);
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
        $q = 'SELECT * FROM hoods;';
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
        $q = 'SELECT * FROM hoods WHERE ID="0";';
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

?>
