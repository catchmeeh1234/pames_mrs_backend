<?php
    include_once "../includes/class-autoload.inc.php";
    $remarks = $_GET['remarks'];

    $obj = new LogicNumber();
    echo json_encode($obj->fetchLogicNumber($remarks));