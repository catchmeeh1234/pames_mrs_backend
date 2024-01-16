<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";
    //require_once './user.class.php';
    $discount_name = $_GET['discount_name'];


    $obj = new Discount();
    echo json_encode($obj->fetchDiscount($discount_name));