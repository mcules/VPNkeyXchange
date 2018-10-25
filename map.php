<?php
include("function.php");

const DEBUG = false;

if ($_REQUEST['action'] == "polyhoods") {
	$json = getPolyhoods();
}
echo json_encode($json);