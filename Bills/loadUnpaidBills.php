<?php

    include_once "../includes/class-autoload.inc.php";

    $data = $_GET['data'];

    $newData = json_decode($data, true);

    $obj = new Bill();
    echo json_encode($obj->fetchUnpaidBills($newData));