<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";
    //require_once './user.class.php';
    $accountNumber = $_GET['accountNumber'];

    $obj = new Bill();
    echo json_encode($obj->fetchBillAdjustmentByAccNo($accountNumber));