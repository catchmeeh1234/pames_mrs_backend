<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";
    //require_once './user.class.php';
    $orNumber = $_GET['orNumber'];

    $obj = new OfficialReceipt();
    echo json_encode($obj->fetchORBillingByORNo($orNumber));