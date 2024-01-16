<?php
    header('Access-Control-Allow-Origin: *');
    
    include_once "../includes/class-autoload.inc.php";
    // require_once "./includes/connect.inc.php";
    // require_once './classes/document.class.php';
    $division = $_GET['division'];
    $status = $_GET['status'];

    $obj = new Notification();
    echo json_encode($obj->viewNotification($division, $status));