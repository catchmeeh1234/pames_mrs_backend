<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";


    $notif_details = $_POST['notif_details'];
    $role = $_POST['role'];

    $object = new Notification();
    //echo json_encode($object->markAllAsRead($notif_details));
    echo $object->markAllAsRead($notif_details, $role);