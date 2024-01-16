<?php
    //header('Access-Control-Allow-Origin: *');
    include_once "../includes/class-autoload.inc.php";
    $chargeInfo = $_POST['chargeInfo'];

    $obj = new Charges();
    echo json_encode($obj->addCharges($chargeInfo));