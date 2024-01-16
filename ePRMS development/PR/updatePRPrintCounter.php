<?php
    header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";
    // require_once "./includes/connect.inc.php";
    // require_once './classes/document.class.php';


    $prno = $_POST['prno']; 
    //$numberOfItems = $_POST['number_of_items'];

    $editPR = new PR();

    
    echo json_encode($editPR->updatePrPrintCounter($prno));
    //$addDocument->addPRequest($prno,$datecreated,$requestor,$designation,$division,$purpose,$prstatus);