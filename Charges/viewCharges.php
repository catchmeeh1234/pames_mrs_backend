<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";
    //require_once './user.class.php';


    $obj = new Charges();
    echo json_encode($obj->fetchCharges());