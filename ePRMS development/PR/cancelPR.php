<?php
    header('Access-Control-Allow-Origin: *');
    // header('Access-Control-Allow-Headers: Content-Type');
    // header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    // header("Content-type: application/json");

    include_once "../includes/class-autoload.inc.php";
    // require_once "./includes/connect.inc.php";
    // require_once './classes/document.class.php';

    $prno = $_POST['prno'];
    $remarks = $_POST['remarks'];
    $pr_status = $_POST['pr_status'];
    $pr_details_status = $_POST['pr_request_status'];
    $name = $_POST['name'];

    if ($pr_details_status == "For DM Approval") {
        if ($pr_status == "Approve") {
            $stat = "For Budget Checking";
        }
        elseif ($pr_status == "Disapprove")    {
            $stat = "Disapprove";
        }
        elseif ($pr_status == "Cancelled") {
            $stat = "Cancelled";
        } 
        elseif ($pr_status == "Re-route") {
            $stat = $pr_status;
        }
    }
    elseif ($pr_details_status == "For Budget Checking") {
        if ($pr_status == "Approve") {
            $stat = "For Cash Allocation";
        }
        elseif ($pr_status == "Disapprove")    {
            $stat = "Disapprove";
        }
        elseif ($pr_status == "Cancelled") {
            $stat = "Cancelled";
        }elseif ($pr_status == "Re-route") {
            $stat = $pr_status;
        }
    }
    elseif ($pr_details_status == "For Cash Allocation") {
        if ($pr_status == "Approve") {
            $stat = "For Printing";
        }
        elseif ($pr_status == "Disapprove")    {
            $stat = "Disapprove";
        }
        elseif ($pr_status == "Cancelled") {
            $stat = "Cancelled";
        }
        elseif ($pr_status == "Re-route") {
            $stat = $pr_status;
        }
    } elseif($pr_details_status == "For Printing") {
         $stat = $pr_status;
    } else {
        exit("Error");
    }

    $obj = new PR();
    //$addDocument->addPRequest($prno,$datecreated,$requestor,$designation,$division,$purpose,$prstatus);
    //$addDocument->addPRequest($prno,$datecreated,$requestor,$designation,$division,$purpose,$prstatus,$pr_item,$pr_quantity,$pr_unit,$pr_price);

    echo json_encode($obj->cancelPR($prno, $remarks, $pr_details_status, $stat, $name));
    //$addDocument->addPRequest($prno,$datecreated,$requestor,$designation,$division,$purpose,$prstatus);