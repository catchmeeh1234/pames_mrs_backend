<?php
    header('Access-Control-Allow-Origin: *');
    // header('Access-Control-Allow-Headers: Content-Type');
    // header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    // header("Content-type: application/json");

    include_once "../includes/class-autoload.inc.php";
    // require_once "./includes/connect.inc.php";
    // require_once './classes/document.class.php';

    $prno = $_GET['prno'];

    $obj = new PR();
    //$addDocument->addPRequest($prno,$datecreated,$requestor,$designation,$division,$purpose,$prstatus);
    //$addDocument->addPRequest($prno,$datecreated,$requestor,$designation,$division,$purpose,$prstatus,$pr_item,$pr_quantity,$pr_unit,$pr_price);

    echo json_encode($obj->loadPrAndItems($prno));
    //$addDocument->addPRequest($prno,$datecreated,$requestor,$designation,$division,$purpose,$prstatus);