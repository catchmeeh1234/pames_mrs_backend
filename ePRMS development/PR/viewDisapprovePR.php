<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";

    $division = $_GET['division'];

    $object = new PR();
    echo json_encode($object->viewDisapprovePR($division));