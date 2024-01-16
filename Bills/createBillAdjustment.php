<?php
    include_once "../includes/class-autoload.inc.php";
    $billAdjustmentDetails = $_POST['billAdjustmentDetails'];

    $obj = new Bill();
    echo json_encode($obj->createBillAdjustment($billAdjustmentDetails));