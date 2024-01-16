<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";
    //require_once './user.class.php';
    $billNumber = $_GET['billNumber'];

    $obj = new Bill();
    echo json_encode($obj->fetchBillAdjustmentByBillNo($billNumber));