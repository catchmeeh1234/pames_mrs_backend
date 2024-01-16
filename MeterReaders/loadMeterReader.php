<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";
    //require_once './user.class.php';
    $reader_id = $_GET['reader_id'];

    $obj = new MeterReader();
    echo json_encode($obj->fetchMeterReader($reader_id));