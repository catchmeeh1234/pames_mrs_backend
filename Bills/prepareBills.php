<?php
    include_once "../includes/class-autoload.inc.php";
    $zones = $_POST['zones'];
    $billingMonth = $_POST['billingMonth'];
    $meterReader = $_POST['meterReader'];
    $createdBy = $_POST['createdBy'];

    $obj = new Bill();
    echo json_encode($obj->prepareBills($zones, $billingMonth, $meterReader, $createdBy));