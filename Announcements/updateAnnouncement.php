<?php
    include_once "../includes/class-autoload.inc.php";
    $message = $_POST['message'];
    $contactNo = $_POST['contactNo'];

    $obj = new Announcement();
    $result1 = $obj->updateAnnouncementMessage($message);
    $result2 = $obj->updateAnnouncementContactNo($contactNo);

    if ($result1["status"] == $result2["status"]) {
        echo json_encode(array('status'=> 'Announcement updated'));
    } else {
        echo json_encode(array('status'=> 'error updating announcement '. $result1["status"]));
    }