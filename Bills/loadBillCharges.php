<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";
    //require_once './user.class.php';
    $billno = $_GET['billno'];

    $obj = new Bill();
    echo json_encode($obj->fetchBillCharges($billno));