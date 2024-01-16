<?php
     header('Access-Control-Allow-Origin: *');

     include_once "../includes/class-autoload.inc.php";

    $div = $_GET['division'];

    $countDocuments = new PR();
    echo $countDocuments->loadPRCounter($div);
