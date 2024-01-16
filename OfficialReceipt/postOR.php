<?php
    include_once "../includes/class-autoload.inc.php";

    $orDetails = $_POST['orDetails'];

    $obj = new OfficialReceipt();
    echo json_encode($obj->postOR($orDetails));