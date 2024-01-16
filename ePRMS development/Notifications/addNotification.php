<?php
    header('Access-Control-Allow-Origin: *');
    
    include_once "../includes/class-autoload.inc.php";
    // require_once "./includes/connect.inc.php";
    // require_once './classes/document.class.php';

    $title = $_POST['title'];
    $message = $_POST['message'];
    $role = $_POST['role'];
	$division = $_POST['division'];
    $status = $_POST['status'];
    $prno = $_POST['prno'];

    $obj = new Notification();
    echo json_encode($obj->addNotification($title, $message,  $role, $division, $status, $prno));