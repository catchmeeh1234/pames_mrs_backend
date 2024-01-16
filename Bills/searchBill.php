<?php
    include_once "../includes/class-autoload.inc.php";
    $billingMonth = $_GET['billingMonth'];
    $billStatus = $_GET['billStatus'];
    $zone = $_GET['zone'];

    $obj = new Bill();
    echo json_encode($obj->searchBill($billingMonth, $billStatus, $zone));