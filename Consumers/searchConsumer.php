<?php
    include_once "../includes/class-autoload.inc.php";

    $search = $_GET['search'];
    $status = $_GET['status'];
    $zone = $_GET['zone'];

    $obj = new Consumer();
    echo json_encode($obj->searchConsumer($search, $zone, $status));