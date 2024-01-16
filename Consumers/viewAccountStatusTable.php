<?php
    include_once "../includes/class-autoload.inc.php";

    $account_no = $_GET['accountNo'];

    $obj = new Consumer();
    echo json_encode($obj->fetchAccountStatusTable($account_no));