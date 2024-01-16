<?php
    include_once "../includes/class-autoload.inc.php";
    $receipt = $_POST['receipt'];

    $obj = new Bill();
    echo json_encode($obj->printBill($receipt));