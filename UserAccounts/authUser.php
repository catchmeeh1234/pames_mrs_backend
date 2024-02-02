<?php
    //header('Access-Control-Allow-Origin: *');

    include_once "../includes/class-autoload.inc.php";
    //require_once './user.class.php';

    $username = $_GET['username'];
    $password = $_GET['password'];

    $user = new User();
    echo json_encode($user->login($username, $password));