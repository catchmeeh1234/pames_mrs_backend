<?php
    include_once "../includes/class-autoload.inc.php";
    $accountNumber = $_GET['accountNumber'];

    $obj = new OfficialReceipt();
    echo json_encode($obj->fetchORDetailsByAccountNo($accountNumber));