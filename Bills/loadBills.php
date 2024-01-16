<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";
    //require_once './user.class.php';
    $accno = $_GET['accno'];

    $obj = new Bill();
    echo json_encode($obj->fetchBills($accno));