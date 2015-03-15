<?php
/**
 * Handle VPN-Key-Exchange request for Freifunk-Franken.
 *
 * @author RedDog <reddog@mastersword.de>
 * @author delphiN <freifunk@wunschik.net>
 * @author Mose <mose@fabfolk.com>
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 */
const DEFAULT_HOOD_ID = 1;
const INVALID_MAC = 'AAAAAAAAAAAA';
const DEBUG = false;

/**
 * Singelton DB instance
 */
class db{
  private static $instance = NULL;
  private function __construct(){
  }
  public static function getInstance(){
    if(!self::$instance){
      require('config.inc.php');
      self::$instance = new PDO('mysql:host='.$mysql_server.';dbname='.$mysql_db,$mysql_user,$mysql_pass);
      self::$instance->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    }
    return self::$instance;
  }
  private function __clone(){
  }
}

/**
 * returns details error msg (as json)
 *
 * @param integer $code
 *          HTTP error 400, 500 or 503
 * @param string $msg
 *          Error message text
 */
function showError($code,$msg){
  if($code == 400)
    header('HTTP/1.0 400 Bad Request');
  elseif($code == 500)
    header('HTTP/1.0 500 Internal Server Error');
  elseif($code == 503)
    header('HTTP/1.0 503 Service Unavailable');

  header('Content-Type: application/json');

  $errorObject = array('error'=>array('msg'=>$msg,'url'=>'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']));
  print_r(json_encode($errorObject));
}

/**
 * Haversine distance function in km
 * https://en.wikipedia.org/wiki/Haversine_formula
 *
 * @param double $lat1
 *          latitude point 1
 * @param double $lon1
 *          longitude point 1
 * @param double $lat2
 *          latitude point 2
 * @param double $lon2
 *          longitude point 2
 * @return integer distance between the points in km
 */
const EARTH_RADIUS = 6371;

function distance_haversine($lat1,$lon1,$lat2,$lon2){
  $delta_lat = $lat1-$lat2;
  $delta_lon = $lon1-$lon2;
  $alpha = $delta_lat/2;
  $beta = $delta_lon/2;
  $a = sin(deg2rad($alpha))*sin(deg2rad($alpha))+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin(deg2rad($beta))*sin(deg2rad($beta));
  $c = asin(min(1,sqrt($a)));
  $distance = 2*EARTH_RADIUS*$c;
  $distance = round($distance,3);
  return $distance;
}

/**
 * Try to read the geo coodinates from netmon and
 * return them as an array [lat, lon].
 * In case of error return empty array.
 *
 * @param $mac search for the router by the mac adress or by name
 * @return array[lat, lon] or []
 */
function getLocationByMacOrName($mac){
  $url = 'https://netmon.freifunk-franken.de/api/rest/router/'.$mac;

  if(!$netmon_response = simplexml_load_file($url)) {
    debug('ERROR: Failed to open '.$url);
    return [];
  }

  if($netmon_response->request->error_code > 0){
    debug('WARN: '.$netmon_response->request->error_message);
    return [];
  }

  // get geo-location
  $nodeLat = floatval($netmon_response->router->latitude);
  $nodeLon = floatval($netmon_response->router->longitude);
  if ($nodeLat == 0 || $nodeLon == 0){
    debug('WARN nodeLat: '.$nodeLat.', nodeLon: '.$nodeLon);
    return [];
  }

  debug('nodeLat: '.$nodeLat.', nodeLon: '.$nodeLon);
  return array($nodeLat,$nodeLon);
}

/**
 * Check is the given geo coordinates are within one of the hoods.
 *
 * @param double $lat
 *          latitude point 1
 * @param double $lon
 *          longitude point 1
 * @return integer hood-id
 */
function getHoodByGeo($lat,$lon){
  $current_hood_dist=99999999;
  $current_hood=DEFAULT_HOOD_ID;

  // load hoods from DB
  try {
    $rs = db::getInstance()->prepare('SELECT * FROM `hoods`');
    $rs->execute();
  }
  catch(PDOException $e) {
    exit(showError(500,$e));
  }

  // check for every hood if it's nearer than the hood before
  while($result = $rs->fetch(PDO::FETCH_ASSOC)){
    debug("\n\nhood: ".$result['name']);

    if(is_null($result['lat']) || is_null($result['lon']))
      continue;

    debug('hoodCenterLat: '.$result['lat'].', hoodCenterLon: '.$result['lon'].', hoodID: '.$result['ID']);

    $distance = distance_haversine($result['lat'],$result['lon'],$lat,$lon);
    debug('distance: $distance');

    if ($distance <= $current_hood_dist) {
      debug('Node belongs to Hood '.$result['ID'].'('.$result['name'].')');
      $current_hood_dist = $distance;
      $current_hood = $result['ID'];
    }
  }

  return $current_hood;
}

function debug($msg){
  if(DEBUG)
    print_r($msg."\n");
}

// ----------------------------------------------------------------------------

// get parameters and initialice settings
if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'])
  $ip = $_SERVER ['HTTP_X_FORWARDED_FOR'];

if(isset($_GET['mac']) && $_GET['mac'])
  $mac = $_GET ['mac'];
else
  $mac = INVALID_MAC;

if(isset($_GET['name']) && $_GET['name'])
  $name = $_GET ['name'];

if(isset($_GET['key']) && $_GET['key'])
  $key = $_GET ['key'];

if(isset($_GET['port']) && $_GET['port'])
  $port = $_GET ['port'];
else
  $port = 10000;

$hood = DEFAULT_HOOD_ID;

// insert or update the current node in the database
if(isset($ip) && $ip && isset($name) && $name && isset($key) && $key) {
  if(!preg_match('/^([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])(\.([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]{0,61}[a-zA-Z0-9]))*$/',$name))
    exit(showError(400,'invalid name'));

  if($mac != INVALID_MAC)
    $sql = 'SELECT * FROM nodes WHERE mac=:mac;';
  else
    $sql = 'SELECT * FROM nodes WHERE (mac=\'000000000000\' OR mac=\''.INVALID_MAC.'\') AND (name=:name);';

  try{
    $rs = db::getInstance()->prepare($sql);

    if($mac != INVALID_MAC)
      $rs->bindParam(':mac',$mac);
    else
      $rs->bindParam(':name',$name);

    $rs->execute ();
  }
  catch(PDOException $e) {
    exit(showError(500,$e));
  }

  if($rs->rowCount() > 1)
    exit(showError(500,'To much nodes with mac='.$mac.', name='.$name));

  if($rs->rowCount() == 1){
    $result = $rs->fetch(PDO::FETCH_ASSOC);

    $hood = $result['hood_ID'];

    if (!$result['readonly']) {
      $updateHood=false;
      if (!$result['isgateway']) {
        // discover the best hood-id from netmons geo-location
        $location = getLocationByMacOrName($mac == INVALID_MAC ? $name : $mac);

        if($location && $location[0] && $location[1]) {
          $hood = getHoodByGeo($location[0],$location[1]);

          if ($hood != $result['hood_ID']) {
            $updateHood=true;
          }
        }
      }

      if ($updateHood)
        $sql = 'UPDATE nodes SET ip=:ip, mac=:mac, name=:name, `key`=:key, port=:port, timestamp=CURRENT_TIMESTAMP, hood_ID=:hood WHERE ID=:id';
      else
        $sql = 'UPDATE nodes SET ip=:ip, mac=:mac, name=:name, `key`=:key, port=:port, timestamp=CURRENT_TIMESTAMP WHERE ID=:id';
      try{
        $rs = db::getInstance()->prepare($sql);
        $rs->bindParam(':id',$result['ID'],PDO::PARAM_INT);
        $rs->bindParam(':ip',$ip);
        $rs->bindParam(':mac',$mac);
        $rs->bindParam(':name',$name);
        $rs->bindParam(':key',$key);
        $rs->bindParam(':port',$port);
        if ($updateHood)
          $rs->bindParam(':hood',$hood);
        $rs->execute();
      }
      catch(PDOException $e) {
        exit(showError(500,$e));
      }
    }
  }
  else{
    $location = getLocationByMacOrName($mac == INVALID_MAC ? $name : $mac);

    if($location && $location[0] && $location[1])
      $hood = getHoodByGeo($location[0],$location[1]);

    $sql = 'INSERT INTO nodes(ip,mac,name,`key`,port,readonly,isgateway,hood_ID) VALUES (:ip,:mac,:name,:key,:port,0,0,:hood);';
    try{
      $rs = db::getInstance()->prepare($sql);
      $rs->bindParam(':ip',$ip);
      $rs->bindParam(':mac',$mac);
      $rs->bindParam(':name',$name);
      $rs->bindParam(':key',$key);
      $rs->bindParam(':port',$port);
      $rs->bindParam(':hood',$hood);
      $rs->execute ();
    }
    catch(PDOException $e) {
      exit(showError(500,$e));
    }
  }
}

// return either all nodes (if gateway) or all gateways (if node) from the hood
try{
  if (isset($result) && is_array($result) && $result['isgateway'])
    $sql = 'SELECT * FROM nodes WHERE hood_ID=:hood;';
  else
    $sql = 'SELECT * FROM nodes WHERE hood_ID=:hood AND isgateway=\'1\';';
  $rs = db::getInstance()->prepare($sql);
  $rs->bindParam(':hood',$hood);
  $rs->execute();
}
catch(PDOException $e) {
  exit(showError(500,$e));
}

// return results in a easy parsable way
if($rs->rowCount() > 0){
  while($result = $rs->fetch(PDO::FETCH_ASSOC)){
    $filename = $result['mac'];

    if($filename == INVALID_MAC || $filename == '000000000000')
      $filename = $result['name'];

    echo '####'.$filename.".conf\n";
    echo '#name "'.$result['name']."\";\n";
    echo 'key "'.$result['key']."\";\n";
    if(preg_match("/[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+/",$result['ip']))
      echo 'remote ipv4 "'.$result['ip'].'" port '.$result['port']." float;\n";
    else
      echo 'remote ipv6 "'.$result['ip'].'" port '.$result['port']." float;\n";

    echo "\n";
  }
  echo "###\n";
}

// vim: expandtab:sw=2
?>
