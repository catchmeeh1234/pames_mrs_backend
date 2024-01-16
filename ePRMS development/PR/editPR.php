<?php
    header('Access-Control-Allow-Origin: *');
    // header('Access-Control-Allow-Headers: Content-Type');
    // header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    // header("Content-type: application/json");

    include_once "../includes/class-autoload.inc.php";
    // require_once "./includes/connect.inc.php";
    // require_once './classes/document.class.php';


    $pr_details = $_POST['pr_details']; 
    //$numberOfItems = $_POST['number_of_items'];

    $editPR = new PR();

    $editPR->editPR($pr_details);
    //$addDocument->addPRequest($prno,$datecreated,$requestor,$designation,$division,$purpose,$prstatus);