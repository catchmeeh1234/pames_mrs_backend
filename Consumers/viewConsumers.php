<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";
    //require_once './user.class.php';
    $top = $_GET['top'];

    $obj = new Consumer();
    echo json_encode($obj->fetchConsumers($top), JSON_PRETTY_PRINT);