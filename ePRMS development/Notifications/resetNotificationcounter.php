<?php
    header('Access-Control-Allow-Origin: *');
    
    include_once "../includes/class-autoload.inc.php";

    $userid = $_POST['userid'];

    $obj = new Notification();
    echo $obj->resetNotificationcounter($userid);