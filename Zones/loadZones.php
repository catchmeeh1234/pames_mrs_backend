<?php
    //header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    include_once "../includes/class-autoload.inc.php";
    
    $obj = new Zone();
    //echo json_encode($obj->fetchZones());

    echo json_encode($obj->fetchZones(), JSON_PRETTY_PRINT);