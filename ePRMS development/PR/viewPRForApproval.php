<?php
    // header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";

    $div = $_GET['div'];
    $role = $_GET['role']; 

    $object = new PR();
    echo json_encode($object->viewPRForApproval($div, $role));