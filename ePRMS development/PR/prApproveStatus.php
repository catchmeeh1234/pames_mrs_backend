<?php
    header('Access-Control-Allow-Origin: *');
    // header('Access-Control-Allow-Headers: Content-Type');
    // header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    // header("Content-type: application/json");

    include_once "../includes/class-autoload.inc.php";
    // require_once "./includes/connect.inc.php";
    // require_once './classes/document.class.php';

    $prno = $_GET['prnum'];
    $prstat  = $_GET['status'];
    $name = $_GET['name'];
    $status = $_GET['stat'];

    if ($prstat == "For DM Approval") {
        if ($status == "Approve") {
        $stat = "For Budget Checking";
        }
        elseif ($status == "Disapprove") {
        $stat = "Disapprove";
        }
    }
    elseif ($prstat == "For Budget Checking") {
        if ($status == "Approve") {
        $stat = "For Cash Allocation";
        }
        elseif ($status == "Disapprove")    {
        $stat = "Disapprove";
        }
    }
    elseif ($prstat == "For Cash Allocation") {
        if ($status == "Approve") {
        $stat = "For Printing";
        }
        elseif ($status == "Disapprove")    {
        $stat = "Disapprove";
        }
    }

    
    
    $updateDoc = new PR();

    $updateDoc->updateDocApprove($prno,$name,$stat);