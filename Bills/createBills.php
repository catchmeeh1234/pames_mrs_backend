<?php
    include_once "../includes/class-autoload.inc.php";
    $preparedBills = $_POST['preparedBills'];
    $billingMonth = $_POST['billingMonth'];
    $zones = $_POST['zones'];

    $obj = new Bill();
    echo json_encode($obj->createBills($preparedBills, $billingMonth, $zones));