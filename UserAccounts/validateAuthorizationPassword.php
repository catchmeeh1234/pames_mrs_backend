<?php
    include_once "../includes/class-autoload.inc.php";

    $password = $_GET['password'];

    $user = new User();
    echo json_encode($user->validateAuthorizationPassword($password));