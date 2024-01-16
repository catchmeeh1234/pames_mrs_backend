<?php
    include_once "../includes/class-autoload.inc.php";
    $rate_name = $_GET['rate_name'];

    $obj = new RateSchedule();
    echo json_encode($obj->fetchRateSchedule($rate_name));