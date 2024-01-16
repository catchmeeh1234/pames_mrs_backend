<?php
//require_once "../includes/class-autoload.inc.php";
header('Access-Control-Allow-Origin: *');

// require_once "./connect.inc.php";
// require_once "./logicnumbers.class.php";

class Notification extends Connect
{
    public function __construct()
    {
        
    }

    public function editNotification($original_prno, $edited_prno) {
        $connection = $this->openConnection();
        $sql = "UPDATE notifications SET notif_prno = :edited_prno WHERE notif_prno = :original_prno";
        $stmt = $connection->prepare($sql);
        $stmt->execute(array(':edited_prno' => $edited_prno, ':original_prno' => $original_prno));
        $count = $stmt->rowCount();

        echo $edited_prno .  " | ";
        echo $original_prno;
        if ($count == 0) {
            print_r($stmt->errorInfo());
        } else {
            return "success";
        }
    }

    public function updateNotificationIsRead($prno, $status, $role) {
        $connection = $this->openConnection();

        if ($role === "Encoder") {
            $sql = "UPDATE notifications SET notif_encoder_isRead = :notif_encoder_isRead WHERE notif_prno = :pr_no AND notif_status= :status";
            $stmt = $connection->prepare($sql);
            $stmt->execute(array(':notif_encoder_isRead' => '1', ':pr_no' => $prno, ':status' => $status));
        } else {
            $sql = "UPDATE notifications SET notif_isRead = :notif_isRead WHERE notif_prno = :pr_no AND notif_status= :status";
            $stmt = $connection->prepare($sql);
            $stmt->execute(array(':notif_isRead' => '1', ':pr_no' => $prno, ':status' => $status));
        }

        $count = $stmt->rowCount();

        if ($count == 1) {
            return array("status" => "success");
        } else {
            print_r($stmt->errorInfo());
            return array();
        }
    }

    public function updateOneNotification($notif_id, $role) {
        $connection = $this->openConnection();

        if ($role === "Encoder") {
            $sql = "UPDATE notifications SET notif_encoder_isRead = :notif_encoder_isRead WHERE notif_id = :notif_id";
            $stmt = $connection->prepare($sql);
            $stmt->execute(array(':notif_encoder_isRead' => '1', ':notif_id' => $notif_id));
        } else {
            $sql = "UPDATE notifications SET notif_isRead = :notif_isRead WHERE notif_id = :notif_id";
            $stmt = $connection->prepare($sql);
            $stmt->execute(array(':notif_isRead' => '1', ':notif_id' => $notif_id));
        }

        $count = $stmt->rowCount();

        if ($count == 1) {
            return array("status" => "success");
        } else {
            //print_r($stmt->errorInfo());
            return array();
        }
    }

    public function addNotification($title, $message, $role, $division, $status, $prno) {
        $datenow = date("Y-m-d h:i:s A");

        if ($title == "" || $message == "" || $role == "" || $division == "" || $status == "" || $prno == "") {
            exit("Failed");
        }

        $connection = $this->openConnection();

        $sql = "INSERT INTO notifications (notif_title, notif_message, notif_datetime, notif_role, notif_division, notif_status, notif_prno) 
                VALUES(:title, :message, :datetime, :role, :division, :status, :prno)";
        $stmt = $connection->prepare($sql);
        $stmt->execute(array(':title' => $title, ':message' => $message,':datetime' => $datenow, ':role' => $role, ':division' => strtoupper($division), ':status' => $status, ':prno' => $prno));
        $count = $stmt->rowCount();

        if ($count == 1) {
            //check if For DM approval
            //update User's notification counter
            $sql = "UPDATE UserAccounts SET notification_counter = notification_counter + 1 ";

            if ($status == "For DM Approval") {
                $access = 'Approver';
                $sql .= "WHERE Access='$access' AND Division='$division'";
            } elseif ($status == "For Budget Checking") {
                $access = 'Budget';
                $sql .= "WHERE Access='$access'";
            } elseif ($status == "For Cash Allocation") {
                $access = 'Cash';
                $sql .= "WHERE Access='$access'";
            } elseif ($status == "For Printing" || $status == "Cancelled" || strpos($status, 'Disapprove') == true) {
                $access = 'Encoder';
                $sql .= "WHERE Access='$access' AND Division = '$division'";
            } else {
                $access = "none";
            }

            $stmt = $connection->prepare($sql);
            $stmt->execute(); 


            $arrayMessage = array('status' => 'Notification Added Successfully');

        } else {
            print_r($stmt->errorInfo());
            $arrayMessage = array('status' => 'Notification insert failed');
        }

        return $arrayMessage;
    }

    public function markAllAsRead($notif_details, $role) {
        $connection = $this->openConnection();

        $notifications = json_decode($notif_details, true);

        if ($role == "Encoder") {
            foreach ($notifications as $notification) {
                $sql = "UPDATE notifications SET notif_encoder_isRead = :notif_encoder_isRead WHERE notif_id = :notif_id";
                $stmt = $connection->prepare($sql);
                $stmt->execute(array(':notif_encoder_isRead' => '1', ':notif_id' => $notification["notif_id"]));

                 $count = $stmt->rowCount();

                if ($count <> 1) {
                    print_r($stmt->errorInfo());
                    return "failed";
                }
            }
        } else {
            foreach ($notifications as $notification) {
                $sql = "UPDATE notifications SET notif_isRead = :notif_isRead WHERE notif_id = :notif_id";
                $stmt = $connection->prepare($sql);
                $stmt->execute(array(':notif_isRead' => '1', ':notif_id' => $notification["notif_id"]));

                 $count = $stmt->rowCount();

                if ($count <> 1) {
                    print_r($stmt->errorInfo());
                    return "failed";
                }
            }
        }

       

        return "success";
        // if ($role == "Encoder") {
        //     $sql = "UPDATE notifications SET notif_encoder_isRead = :notif_encoder_isRead WHERE notif_status = :notif_status AND notif_division= :notif_division";
        //     $stmt = $connection->prepare($sql);
        //     $stmt->execute(array(':notif_encoder_isRead' => '1', ':notif_status' => $status, ':notif_division' => $division));
        // } elseif ($role == "Approver") {
        //     $sql = "UPDATE notifications SET notif_isRead = :notif_isRead WHERE notif_status = :notif_status AND notif_division= :notif_division";
        //     $stmt = $connection->prepare($sql);
        //     $stmt->execute(array(':notif_isRead' => '1', ':notif_status' => $status, ':notif_division' => $division));
        // } elseif($role == "Budget" || $role == "Cash") {
        //     $sql = "UPDATE notifications SET notif_isRead = :notif_isRead WHERE notif_status = :notif_status";
        //     $stmt = $connection->prepare($sql);
        //     $stmt->execute(array(':notif_isRead' => '1', ':notif_status' => $status));
        // } else {
        //     exit("error");
        // }

    }

    public function updateNotifIsRead($notifid, $role) {
        $connection = $this->openConnection();
        
        if ($role == "Encoder") {
            $sql = "UPDATE notifications SET notif_encoder_isRead = 1 WHERE notif_id = ?";
        } else {
            $sql = "UPDATE notifications SET notif_isRead = 1 WHERE notif_id = ?";
        }

        $stmt = $connection->prepare($sql);
        $stmt->execute([$notifid]);
        $count = $stmt->rowCount();
        if ($count == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function viewNotification($division, $role) {
        $connection = $this->openConnection();

        if ($role == "Encoder") {
            //$sql= "SELECT TOP (100) * FROM notifications JOIN Pr_details ON notifications.notif_prno= Pr_details.pr_no WHERE pr_division = '$division' ORDER BY notif_id DESC";

             $sql= "SELECT TOP (50) * FROM notifications JOIN Pr_details ON notifications.notif_prno= Pr_details.pr_no WHERE pr_division = '$division' AND notif_status = 'For Printing' ORDER BY notif_id DESC";
            //$sql = "SELECT * FROM notifications WHERE notif_division = '$division' ORDER BY notif_id DESC";
        } 
        elseif ($role == "For DM Approval") {
            $sql = "SELECT TOP (50) * FROM notifications WHERE notif_division = '$division' and notif_status = '$role' ORDER BY notif_id DESC";
            //$sql = "SELECT TOP (100) * FROM notifications WHERE notif_division = '$division' and notif_status = '$role' ORDER BY notif_id DESC";

        }
        elseif ($role == "Final Approver") {
            $sql = "SELECT TOP (50) * FROM notifications WHERE notif_status = 'Approved' ORDER BY notif_id DESC";
            //$sql = "SELECT TOP (100) * FROM notifications WHERE notif_status = 'Approved' ORDER BY notif_id DESC";

        }
         else {
            $sql = "SELECT TOP (50) * FROM notifications WHERE notif_status = '$role' ORDER BY notif_id DESC";

            //$sql = "SELECT TOP (100) * FROM notifications WHERE notif_status = '$role' ORDER BY notif_id DESC";
        }
        
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        $count = $stmt->rowCount();
        if ($count == 0) {
            $arrayDocuments = array();
            //echo json_encode($arrayDocuments);
            return $arrayDocuments;
        } else {
            // foreach($documents as $document) {
            //     echo $document['document_id'] . "<br>";
            // }
            return $result;
        }
    }

    public function resetNotificationcounter($userid) {
        $connection = $this->openConnection();
        $sql = "UPDATE UserAccounts SET notification_counter = 0 WHERE id = ?";
        $stmt = $connection->prepare($sql);

        if ($stmt->execute([$userid])) {
            return true;
        } else {
            echo "Update failed: " . $stmt->error;
            return false;
        }
        


    }

}