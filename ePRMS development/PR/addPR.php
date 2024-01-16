<?php
    header('Access-Control-Allow-Origin: *');
    // header('Access-Control-Allow-Headers: Content-Type');
    // header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    // header("Content-type: application/json");

    include_once "../includes/class-autoload.inc.php";
    // require_once "./includes/connect.inc.php";
    // require_once './classes/document.class.php';


    $prno = $_POST['prno'];
    $pr_details = $_POST['pr_details']; 
    $prencoder = $_POST['username'];


    //$count = count($pr_items);

    /*$pr_items = $_POST['items'];
    $pr_quantity = $_POST['quantity'];
    $pr_unit = $_POST['unit'];
    $pr_price = $_POST['cost'];*/

    /*$pr_item = "Blank DVD";
    $pr_quantity = "10";
    $pr_unit = "Piece";
    $pr_price = 250.00;*/


    //echo $prno."<br>".$datecreated."<br>".$requestor."<br>".$designation."<br>".$division."<br>".$purpose."<br>".$prstatus."<br>";
    //echo $pr_items."<br>".$pr_quantity."<br>".$pr_unit."<br>".$pr_price."<br>".$pr_particulars;

    $addDocument = new PR();
    //$addDocument->addPRequest($prno,$datecreated,$requestor,$designation,$division,$purpose,$prstatus);
    //$addDocument->addPRequest($prno,$datecreated,$requestor,$designation,$division,$purpose,$prstatus,$pr_item,$pr_quantity,$pr_unit,$pr_price);

    $addDocument->addPRequest($prno, $pr_details, $prencoder);
    //$addDocument->addPRequest($prno,$datecreated,$requestor,$designation,$division,$purpose,$prstatus);