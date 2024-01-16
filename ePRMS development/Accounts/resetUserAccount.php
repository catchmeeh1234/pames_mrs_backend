<?php
    header('Access-Control-Allow-Origin: *');
    include_once "../includes/class-autoload.inc.php";

    $userid = $_POST['userid']; 

    $resetUser = new User();
    echo json_encode($resetUser->resetUserPassword($userid));