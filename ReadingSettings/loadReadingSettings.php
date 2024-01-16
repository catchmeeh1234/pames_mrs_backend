<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";
    //require_once './user.class.php';
    $name = $_GET['name'];
    $zone = $_GET['zone'];

    $obj = new ReadingSettings();
    echo json_encode($obj->fetchReadingSettings($name, $zone));