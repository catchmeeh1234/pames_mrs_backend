<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";

    $notif_prno = $_POST['notif_prno'];
    $notif_status = $_POST['notif_status'];
	$role = $_POST['role'];
	// $notif_prno = "23-07-0214";
	// $notif_status = "For DM Approval";
	// $role = "Encoder";


    $object = new Notification();
    echo json_encode($object->updateNotificationIsRead($notif_prno, $notif_status, $role));