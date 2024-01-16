<?php
   	include_once "../includes/class-autoload.inc.php";

	$division = $_GET['division'];
	$role = $_GET['role'];

   	$object = new PR();
   	echo json_encode($object->fetchPR($division, $role));

	