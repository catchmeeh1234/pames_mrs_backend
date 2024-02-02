<?php
    include_once "../includes/class-autoload.inc.php";

    $username = $_GET['username'];

    $obj = new Auth();

    echo json_encode($obj->getJWT($username));