<?php
    include_once "../includes/class-autoload.inc.php";

    $user = new User();
    echo json_encode($user->fetchAllUserAccounts());