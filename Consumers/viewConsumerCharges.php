<?php
    include_once "../includes/class-autoload.inc.php";

    $account_no = $_GET['account_no'];

    $obj = new Consumer();
    echo json_encode($obj->fetchConsumerCharges($account_no));