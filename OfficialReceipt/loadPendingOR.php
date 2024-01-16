<?php
    include_once "../includes/class-autoload.inc.php";

    $obj = new OfficialReceipt();
    echo json_encode($obj->fetchPendingOR());