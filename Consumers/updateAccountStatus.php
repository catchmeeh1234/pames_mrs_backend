<?php
    include_once "../includes/class-autoload.inc.php";

    $accountStatusInfo = $_POST['accountStatusInfo'];

    $obj = new Consumer();
    echo json_encode($obj->updateAccountStatus($accountStatusInfo));