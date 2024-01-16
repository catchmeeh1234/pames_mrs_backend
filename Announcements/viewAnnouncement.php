<?php

    include_once "../includes/class-autoload.inc.php";

    $obj = new Announcement();
    echo json_encode($obj->viewAnnouncement());