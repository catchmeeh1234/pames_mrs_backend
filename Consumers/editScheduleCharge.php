<?php
    include_once "../includes/class-autoload.inc.php";

    $scheduleChargeInfo = $_POST['scheduleChargeInfo'];

    $obj = new Consumer();
    echo json_encode($obj->editScheduleCharge($scheduleChargeInfo));