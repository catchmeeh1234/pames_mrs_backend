<?php
    include_once "../includes/class-autoload.inc.php";

    $account_no = $_GET['account_no'];

    $obj = new ConsumerLedger();
    echo json_encode($obj->viewConsumerLedgerData($account_no));