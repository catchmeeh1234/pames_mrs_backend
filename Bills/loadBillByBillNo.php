<?php

    include_once "../includes/class-autoload.inc.php";

    $billno = $_GET['billno'];

    $obj = new Bill();
    echo json_encode($obj->fetchBillByBillno($billno));