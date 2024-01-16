<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";
    //require_once './user.class.php';
    $remarks = $_GET['remarks'];

    $obj = new LogicNumber();
    echo json_encode($obj->fetchLogicNumber($remarks));