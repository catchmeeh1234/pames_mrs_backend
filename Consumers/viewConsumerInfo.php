<?php
    include_once "../includes/class-autoload.inc.php";

    $consumer_id = $_GET['consumer_id'];

    $obj = new Consumer();
    echo json_encode($obj->fetchConsumerInfo($consumer_id));