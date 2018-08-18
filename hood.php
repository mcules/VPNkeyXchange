<?php
try {
	require ("config.inc.php");
	$db = new PDO("mysql:host=$mysql_server;dbname=$mysql_db;charset=utf8mb4", $mysql_user, $mysql_pass);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$rs = $db->prepare ( "SELECT * FROM `hoods`" );
	$rs->execute ();
} catch ( PDOException $e ) {
	exit($e);
}

$hoods = array();
while ( $result = $rs->fetch ( PDO::FETCH_ASSOC ) ) {
	$hood = array();
	$hood['id']   = intval($result['ID']);
	$hood['name'] = $result['name'];
	$hood['net']  = $result['net'];
	if ($result ['lat'] > 0 && $result ['lon'] > 0) {
		$hood['lat'] = floatval($result['lat']);
		$hood['lon'] = floatval($result['lon']);
	}
	echo 'ID: '.$hood['id'].' ; Name: '.$hood['name'].' ; Net: '.$hood['net'].' ; lat: '.$hood['lat'].' ; lon: '.$hood['lon'].' ; <a href="http://keyserver.freifunk-franken.de/v2/?lat='.$hood['lat'].'&long='.$hood['lon'].'">zur Hoodfile</a><br>';
}

?>
