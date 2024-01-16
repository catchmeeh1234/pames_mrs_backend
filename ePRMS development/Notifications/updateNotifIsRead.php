<?php
    header('Access-Control-Allow-Origin: *');
    
    include_once "../includes/class-autoload.inc.php";

    $notifid = $_POST['notifid'];
    $role = $_POST['role'];

    $obj = new Notification();
    echo $obj->updateNotifIsRead($notifid, $role);