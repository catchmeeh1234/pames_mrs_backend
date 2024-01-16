<?php
    header('Access-Control-Allow-Origin: *');
    // header('Access-Control-Allow-Headers: Content-Type');
    // header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    // header("Content-type: application/json");

    include_once "../includes/class-autoload.inc.php";

    $prno = $_GET['fullname'];


    $obj = new Employee();

    echo json_encode($obj->selectEmployee($prno,$datecreated,$requestor,$designation,$division,$purpose,$prstatus,$pr_items,$prencoder));
