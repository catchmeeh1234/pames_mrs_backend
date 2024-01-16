<?php
   	include_once "../includes/class-autoload.inc.php";

   	$object = new PR();
   	echo json_encode($object->JanTotalPR());