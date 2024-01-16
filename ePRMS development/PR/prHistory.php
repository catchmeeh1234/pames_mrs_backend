<?php
    include_once "../includes/class-autoload.inc.php";
    // require_once "./includes/connect.inc.php";
    // require_once './classes/document.class.php';

    $prnum = $_GET['prnum'];


    $prHistory = new PR();
    echo json_encode($prHistory->viewPRHistory($prnum));