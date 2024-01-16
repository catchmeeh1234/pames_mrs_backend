<?php
    include_once "../includes/class-autoload.inc.php";
    $billInfo = $_POST['billInfo'];

    $obj = new Bill();
    echo json_encode($obj->createBill($billInfo));