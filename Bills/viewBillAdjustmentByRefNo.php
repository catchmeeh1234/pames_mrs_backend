<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";
    //require_once './user.class.php';
    $refNo = $_GET['refNo'];

    $obj = new Bill();
    echo json_encode($obj->fetchBillAdjustmentByRefNo($refNo));