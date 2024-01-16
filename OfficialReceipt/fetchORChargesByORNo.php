<?php
    include_once "../includes/class-autoload.inc.php";
    $orNumber = $_GET['orNumber'];

    $obj = new OfficialReceipt();
    echo json_encode($obj->fetchORChargesByORNo($orNumber));