<?php
    header('Access-Control-Allow-Origin: *');
    // header('Access-Control-Allow-Headers: Content-Type');
    // header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    // header("Content-type: application/json");

    include_once "../includes/class-autoload.inc.php";
    // require_once "./includes/connect.inc.php";
    // require_once './classes/document.class.php';


    $userAccountDetails = $_POST['userAccountDetails']; 

    if (isset($userAccountDetails)) {
        $editUser = new User();
        echo json_encode($editUser->addUserAccount($userAccountDetails));
    }

    