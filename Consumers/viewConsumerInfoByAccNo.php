<?php
    include_once "../includes/class-autoload.inc.php";

    $accountNo = $_GET['accountNo'];

    $obj = new Consumer();
    echo json_encode($obj->fetchConsumerInfoByAccNo($accountNo));