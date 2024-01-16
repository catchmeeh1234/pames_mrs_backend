<?php
    include_once "../includes/class-autoload.inc.php";
    $billno = $_POST['billno'];
    $accno = $_POST['accno'];
    $postedBy = $_POST['postedBy'];

    $obj = new Bill();
    echo json_encode($obj->postBill($billno, $accno, $postedBy));