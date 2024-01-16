<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";

    $object = new PR();
    echo json_encode($object->getDivision());