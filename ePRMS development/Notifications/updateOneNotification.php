<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";

    $notif_id = $_POST['notif_id'];
    $role = $_POST['role'];

    $object = new Notification();
    echo json_encode($object->updateOneNotification($notif_id, $role));