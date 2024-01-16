<?php
    include_once "../includes/class-autoload.inc.php";

    $consumerInfo = $_POST['consumerInfo'];

    $obj = new Consumer();
    echo json_encode($obj->addConsumer($consumerInfo));