<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";
    //require_once './user.class.php';

    $userid = $_GET['userid'];

    $obj = new User();
    echo $obj->loadNotificationsCounter($userid);