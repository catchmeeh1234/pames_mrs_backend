<?php
//require_once "../includes/class-autoload.inc.php";
header('Access-Control-Allow-Origin: *');

// require_once "./connect.inc.php";
// require_once "./logicnumbers.class.php";
// require_once './Notification.class.php';
//require_once "connect_sqlsrv.php";

class PR extends Connect
{
    private $refno;
    private $number;
    private $file;
    private $target_dir = "files/documents/";
    private $uniqueid;
    private $fileType;

    public function __construct()
    {
        $this->uniqueid = uniqid('', true);
        //"files/documents/";
    }

    public function fetchPR($division, $role)
    {
        $connection = $this->openConnection();

        if ($role == "Encoder") {
            $sql = "SELECT * FROM dbo.Pr_details JOIN Division ON pr_division = division_name WHERE pr_division = '$division' ORDER BY pr_no DESC";
        } else {
            $sql = "SELECT * FROM dbo.Pr_details JOIN Division ON pr_division = division_name ORDER BY pr_no DESC";
        }

        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $count = $stmt->rowCount();

        if ($count == 0) {
            return array();
        } else {
            return $result;
        }
    }

    public function JanTotalPR() {
        $connection = $this->openConnection();

        $year = DATE("Y");

        $sql = "SELECT DISTINCT DATEPART(MONTH, pr_dateCreated) AS month, COUNT(pr_dateCreated) AS total FROM Pr_details WHERE DATEPART(year, pr_dateCreated) = '$year' GROUP BY DATEPART(MONTH, pr_dateCreated)";
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $count = $stmt->rowCount();

        if ($count == 0) {
            return array();
        } else {
            //echo $count;
            return $result;
        }
    }


    public function incrementPRNumber() {
        $connection = $this->openConnection();

        $sql = "SELECT TOP 1 pr_no FROM dbo.Pr_details ORDER BY pr_no DESC";  
        $stmt = $connection->query($sql);  

        while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){
            $prno = $row['pr_no'];
            if (strlen($prno) <= 10) {
                $prnum = substr($prno, -4); 
                $prnum2 = $prnum + 1;
                $prnumber = DATE('y')."-".DATE('m')."-".str_pad($prnum2, 4, '0', STR_PAD_LEFT);   

                return $prnumber;      
            } else {
                $parts = explode("-", $prno);

                $number = intval($parts[2]); // Extract the number part and convert to integer
                $number++; // Increment the number
                $newNumber = str_pad($number, 4, "0", STR_PAD_LEFT); // Format the number with leading zeros
                $newString = $parts[0] . "-" . $parts[1] . "-" . $newNumber; // Rebuild the string

                return $newString;
            }
        }
    }

    public function validatePRNo($prno) {
        $connection = $this->openConnection();
        $sql = "SELECT * FROM Pr_details WHERE pr_no ='$prno'";
        $rows = $connection->prepare($sql);
        $rows->execute();
        $count = $rows->rowCount();
        return $count;
    }

    public function getMeasurementUnits() {
        $connection = $this->openConnection();
        $sql = "SELECT * FROM UnitMeasurement ORDER BY unit_name ASC";
        $rows = $connection->prepare($sql);
        $rows->execute();
        $count = $rows->rowCount();
        if ($count == 0) {
            $array = array('status' => 'No Measurement units found');
            return $array;
        } else {
            $result = $rows->fetchAll();
            return $result;
        }
        
    }

    public function getDivision() {
        $connection = $this->openConnection();
        $sql = "SELECT * FROM Division";
        $rows = $connection->prepare($sql);
        $rows->execute();
        $count = $rows->rowCount();
        if ($count == 0) {
            $array = array('status' => 'No Divisions found');
            return $array;
        } else {
            $result = $rows->fetchAll();
            return $result;
        }
        
    }

    public function getPrLabelStatus() {
        $connection = $this->openConnection();
        $sql = "SELECT * FROM pr_label_status";
        $rows = $connection->prepare($sql);
        $rows->execute();
        $count = $rows->rowCount();
        if ($count == 0) {
            $array = array('status' => 'No pr status found');
            return $array;
        } else {
            $result = $rows->fetchAll();
            return $result;
        }
    }

    public function viewPRForApproval($div, $role) {
        $connection = $this->openConnection();

        $sql = "SELECT * FROM dbo.Pr_details";

        if ($role=='Approver')  {
            //$prstatus = 'For Approve';
            $sql .= " WHERE pr_status='For DM Approval' AND pr_division='$div'";
        }
        elseif ($role=='Budget')  {
            //$prstatus = 'For Budget Checking';
            $sql .= " WHERE pr_status='For Budget Checking'";
        }
        elseif ($role=='Cash')  {
            $sql .= " WHERE pr_status='For Cash Allocation'";
            //$prstatus = 'For Cash';
        }  
        elseif ($role == 'Encoder') {
            $sql .= " WHERE pr_status = 'For Data Encoder'";

        }
        elseif ($role == 'Final Approver') {
            $sql .= " WHERE pr_status = 'For Final Approver'";

        }
        /*elseif ($role=='Administrator')  {
            $prstatus = 'For Approve';
        }*/
        else { }

        $sql .= " ORDER BY timestamp DESC";

        //$sql = "SELECT * FROM dbo.Pr_details WHERE pr_division='$div' AND pr_status='$prstatus' ORDER BY pr_dateCreated, pr_no DESC";
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $count = $stmt->rowCount();

        if ($count == 0) {
            return array();
        } else {
            return $result;
        }
    }

    public function viewDisapprovePR($division) {
        if ($division == null) {
            exit("Error No division");
        }

        $connection = $this->openConnection();
        $sql = "SELECT * FROM Pr_details WHERE pr_status LIKE '%disapprove%' AND pr_division= '$division'";
        $rows = $connection->prepare($sql);
        $rows->execute();
        $count = $rows->rowCount();
        if ($count == 0) {
            $array = array();
            return $array;
        } else {
            $result = $rows->fetchAll();
            return $result;
        }

    }

    public function cancelPR($prno, $remarks, $pr_original_status, $pr_status, $name) {
        if ($remarks == "") {
            $remarks = null;
        }


        if ($pr_status == "Disapprove") {
            $pr_new_status = $pr_status . "(" . $pr_original_status . ")";
        } elseif ($pr_status == "Re-route") {
           $pr_new_status = "For DM Approval";
        } else {
            $pr_new_status = $pr_status;
        }


        $connection = $this->openConnection();
        $sql = "UPDATE Pr_details SET pr_status = :prstatus, remarks = :prremarks WHERE pr_no = :prno";
        $stmt = $connection->prepare($sql);
        $stmt->execute(array(':prstatus' => $pr_new_status, ':prremarks' => $remarks, ':prno' => $prno));
        $count = $stmt->rowCount();

        if ($count == 1) {
            $arrayMessage = array('status' => 'Success');
            
        } else {
            print_r($stmt->errorInfo());
            return $arrayMessage = array('status' => 'Failed');
        }

        $currentdate = DATE("Y-m-d h:i:s a");

        if ($pr_status == "Re-route") {
           $pr_new_status = $pr_status . "d by " . $name;
        } elseif ($pr_status == "Disapprove") {
            $pr_new_status = $pr_status . "(" . $pr_original_status . ")";
        } else {
            $pr_new_status = $pr_status;
        }


        $sql2 = "INSERT INTO Pr_status (pr_no, pr_datetime, pr_status, pr_updatedBy) VALUES (:prno, :pr_datetime, :pr_status, :pr_updatedBy)";
        $stmt2 = $connection->prepare($sql2);
        $stmt2->execute(array(':prno' => $prno, ':pr_datetime' => $currentdate,':pr_status' => $pr_new_status, ':pr_updatedBy' => $name));
        $count2 = $stmt2->rowCount();
        //echo $count2;
        if( $stmt2 === false ) {
            die(print_r($stmt2->errorInfo()));
        }

        return $arrayMessage;
    }

    public function editPR($prdetails) {
        $obj = json_decode($prdetails, true);

        $pr_items_table = ["item", "qty", "unit", "cost"];

        //update table PR_details
        $connection = $this->openConnection();
        $sql = "UPDATE Pr_details SET pr_no = :prno, pr_requestor = :pr_requestor, pr_designation = :pr_designation, pr_division = :pr_division, pr_purpose = :pr_purpose, remarks = :remarks , pr_title = :pr_title WHERE pr_no = :pr_no_hidden";
        $stmt = $connection->prepare($sql);
        $stmt->execute(array(':prno' => $obj['pr_no'], ':pr_requestor' => $obj['pr_requestor'], ':pr_designation' => $obj['pr_designation'], ':pr_division' => $obj['pr_division'], ':pr_purpose' => $obj['pr_purpose'], ':remarks' => $obj['remarks'], ':pr_title' => $obj['pr_title'], ':pr_no_hidden' => $obj['pr_no_hidden']));
        $count = $stmt->rowCount();

        if ($count != 1) {
            print_r($stmt->errorInfo());
            $arrayMessage = array('status' => 'Failed');
            exit("Failed1");
        } 



        //query specific item
        $qryItem = "SELECT * FROM Pr_item_details WHERE pr_no= :pr_no";
        $selectItems = $connection->prepare($qryItem);
        $selectItems->execute(array(':pr_no' => $obj["pr_no"]));
        $resultItems = $selectItems->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resultItems as $row) {
       
            $prItems_id = $row['prItems_id'];

            //delete sub items for the PR ITEM
            $sql1 = "DELETE FROM Pr_subitem_details WHERE prItems_id = ?";
            $deleteSubItems = $connection->prepare($sql1);
            $deleteSubItems->execute([$prItems_id]);
            $countSubItems = $deleteSubItems->rowCount();

        }
            //delete items for the PR
            $sql4 = "DELETE FROM Pr_item_details WHERE pr_no = ?";
            $deleteitems = $connection->prepare($sql4);
            $deleteitems->execute([$obj['pr_no_hidden']]);
            $count = $deleteitems->rowCount();
            // if ($count != 1) {
            //     print_r($stmt->errorInfo());
            //     $arrayMessage = array('status' => 'Failed');
            //     exit("Failed2");
            // } 

            if (count($obj["items"]) == 0) {
                exit("No items found");
            }   

        
            //insert items query
            $sql = "INSERT INTO Pr_item_details (pr_no, pr_items, pr_quantity, pr_unit, pr_cost, bold_text) 
                    VALUES(:pr_no, :pr_items, :pr_quantity, :pr_unit, :pr_cost, :bold_text)";
            foreach ($obj["items"] as $item) {
                //bold text
                if ($item["boldText"] == 1) {
                    $boldText = "true";
                } else {
                    $boldText = "false";
                }

                $stmt = $connection->prepare($sql);
                $stmt->execute(array(':pr_no' => $obj['pr_no'], ':pr_items' => $item["item"], ':pr_quantity' => $item["qty"], ':pr_unit' => $item["unit"], ':pr_cost' => $item["cost"], ':bold_text' => $boldText));


                //get last inserted id
                $lastInsertedItems_id = $connection->lastInsertId();

                if (count($item["pr_subitems"]) == 0) {
                    echo "no sub items found";
                }


                //insert sub items query
                $sql2 = "INSERT INTO Pr_subitem_details (prItems_id, dpr_items, dpr_quantity, dpr_unit, dpr_cost) 
                        VALUES(:prItems_id, :dpr_items, :dpr_quantity, :dpr_unit, :dpr_cost)";

                foreach ($item["pr_subitems"] as $subitem) {
                    $stmt2 = $connection->prepare($sql2);
                    $stmt2->execute(array(':prItems_id' => $lastInsertedItems_id, ':dpr_items' => $subitem["dpr_items"], ':dpr_quantity' => $subitem["dpr_quantity"], ':dpr_unit' => $subitem["dpr_unit"], ':dpr_cost' => $subitem["dpr_cost"]));
                }
            }

        

       
        
        //$result = array_combine($col, $arrayItems);

        //print_r($result);

        // $stmt->execute($result);
        // $count = $stmt->rowCount();
        //echo $count;
        //print_r($stmt->errorInfo());
            
        
        $editNotif = new Notification();
        echo $editNotif->editNotification($obj['pr_no_hidden'], $obj['pr_no']);
    }

    //public function addPRequest($prno, $datecreated, $requestor, $designation, $division, $purpose, $prstatus)
    public function addPRequest($prno, $pr_details, $prencoder)
    {
        $connection = $this->openConnection();

        $obj = json_decode($pr_details, true);
        $validatePRNo = $this->validatePRNo($prno);

        $time = DATE("Y-m-d h:i:s a");
     
        if ($validatePRNo == 0) {
            
            //insert pr_details to database
            $sql = "INSERT INTO Pr_details (pr_no, pr_dateCreated, pr_requestor, pr_designation, pr_division, pr_purpose, pr_status, timestamp, pr_title) VALUES (:pr_no, :pr_dateCreated, :pr_requestor, :pr_designation, :pr_division, :pr_purpose, :pr_status, :timestamp, :pr_title)";
            $stmt = $connection->prepare($sql);
            $stmt->execute(array(':pr_no' => $prno, ':pr_dateCreated' => $obj["pr_date"], ':pr_requestor' => $obj["pr_requestor"], ':pr_designation' => $obj["pr_designation"], ':pr_division' => $obj["pr_division"], ':pr_purpose' => $obj["pr_purpose"], ':pr_status' => $obj["pr_status"], ':timestamp' => $time, ':pr_title' => $obj["pr_title"]));
            $countt = $stmt->rowCount();





            
            // $currentdate = $datecreated." ".$time;

            //insert pr_status to database
            $sql2 = "INSERT INTO Pr_status (pr_no, pr_datetime, pr_status, pr_updatedBy) VALUES (:pr_no, :pr_datetime, :pr_status, :pr_updatedBy)";
            $stmt2 = $connection->prepare($sql2);
            $stmt->execute(array(':pr_no' => $prno, ':pr_datetime' => $time, ':pr_status' => $obj["pr_status"], ':pr_updatedBy' => $prencoder));

            //print_r($stmt2->errorInfo());

            // $stmt2 = sqlsrv_query( $conn, $sql2, $params2);
            // if( $stmt2 === false ) {
            //      die( print_r( sqlsrv_errors(), true));
            // }

           
            //$pr_number = $obj['prno'];

            $items = $obj['pr_items'];
            if (count($items) == 0) {
                exit("No pr items found");
            }
            foreach ($items as $item) {

                $pr__item = $item['item'];
                $pr__qty = $item['qty'];
                $pr__unit = $item['unit'];
                $pr__cost = $item['cost'];

                if ($item["boldText"] == 1) {
                    $boldText = "true";
                } else {
                    $boldText = "false";
                }


                // INSERT PR_ITEMS TO DATABASE
                $sql3 = "INSERT INTO Pr_item_details (pr_no, pr_items, pr_quantity, pr_unit, pr_cost, bold_text) VALUES (:pr_no, :pr_items, :pr_quantity, :pr_unit, :pr_cost, :bold_text)";
                $stmt3 = $connection->prepare($sql3);
                $stmt3->execute(array(':pr_no' => $prno, ':pr_items' => $pr__item, ':pr_quantity' => $pr__qty, ':pr_unit' => $pr__unit, ':pr_cost' => $pr__cost, ':bold_text' => $boldText));

                //print_r($stmt3->errorInfo());

                //get last inserted id
                $lastInsertedItems_id = $connection->lastInsertId();

                if ($stmt3->rowCount() == 1) {
                    if (count($item["pr_subitems"]) != 0) {

                        foreach ($item["pr_subitems"] as $subitem) {
                            // INSERT PR_SUBITEMS TO DATABASE
                            $sql4 = "INSERT INTO Pr_subitem_details (prItems_id, dpr_items, dpr_quantity, dpr_unit, dpr_cost) 
                                        VALUES (:prItems_id, :dpr_items, :dpr_quantity, :dpr_unit, :dpr_cost)";
                            $stmt4 = $connection->prepare($sql4);
                            $stmt4->execute(array(':prItems_id' => $lastInsertedItems_id, ':dpr_items' => $subitem["dpr_items"], ':dpr_quantity' => $subitem["dpr_quantity"], ':dpr_unit' => $subitem["dpr_unit"], ':dpr_cost' => $subitem["dpr_cost"]));


                        }

                    }             
                }
                              
                    
            }
            echo "Inserted Successfully";
        } else {
            exit("PRNo Already Exists");
        }
    }

    public function loadPRCounter($division)
    {
        $connection = $this->openConnection();
        $sql = "SELECT pr_no FROM Pr_details  WHERE pr_division='$division'";
        $rowItems = $connection->prepare($sql);
        $rowItems->execute();
        $result = $rowItems->fetchAll();
        $count = $rowItems->rowCount();

        return $count;
    }

    public function updateDocApprove($prno, $name, $stat) {

        $currentdate = DATE("Y-m-d h:i:s a");

        // if ($stat == "Disapprove") {
        //     $stat = $stat . "(" . $status . ")";
        // }
               
        //update PR_details pr status to the corresponding status
        $connection = $this->openConnection();
        $sqlup = "UPDATE Pr_details SET pr_status = ?, timestamp = ? WHERE pr_no = ?";
        $stmtup = $connection->prepare($sqlup);
        $stmt->execute([$stat, $currentdate, $prno]);
        $countup = $stmt->rowCount();

        if ($countup == 1) {
            echo "PR Status Updated";
        } else {
            echo "No information available";
        }


        //insert new pr status
        $sql2 = "INSERT INTO Pr_status (pr_no, pr_datetime, pr_status, pr_updatedBy) VALUES (?, ?, ?, ?)";
        $stmt2 = $connection->prepare($sql2);
        $stmt2->execute([$prno, $currentdate, $stat, $name]);

        if ($stmt2 == false) {
            print_r($stmt2->errorInfo());
        } else {
            exit("Success");
        }

    }

    // public function editDocument($id, $file) {
    //     $uploadStatus = $this->validateFile($file);
    //     if ($uploadStatus == 0) {
    //         return false;
    //     } else {
    //         $connection = $this->openConnection();
    //         $currentdate = date("Y-m-d H:i:s");
    //         $documents = $this->selectOneDocument($id);

    //         $target_file = $this->target_dir . basename($file["name"]);
    //         $this->fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    //         $newFileName = $this->uniqueid . "." . $this->fileType;
    //         $file_path = $this->target_dir . $this->uniqueid . "." . $this->fileType;

    //         foreach($documents as $document) {
    //             //create a new file from the directory
    //             if (move_uploaded_file($file["tmp_name"], $file_path)) {
    //                 //delete from the directory
    //                 $path = $document['document_path'];
    //                 if(is_file($path)) 
    //                 unlink($path); 

    //                 $sql = "UPDATE documents SET document_name = ?, document_path = ?, dateUploaded = ?";
    //                 $updateDocument = $connection->prepare($sql);
    //                 $updateDocument->execute([$newFileName, $file_path, $currentdate]);

    //                 if ($updateDocument->rowCount() == 1) {
    //                 echo "Document Update Successfully";
    //                 } else {
    //                     echo "Document not updated";
    //                     return false;
    //                 }
    //             } else {
    //                 echo "Error updating document";
    //             }
    //         }
    //     }      
    // }

    public function loadPrAndItems($prnumber) {
        $connection = $this->openConnection();

        $sql = "SELECT *
                FROM [Pr_details] WHERE Pr_details.pr_no = '$prnumber'";
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        $count = $stmt->rowCount();
        if ($count == 0) {
            //$arrayMessage = array('status' => 'No Purchase Request found');
            //echo json_encode($arrayDocuments);
            return array();
        } else {
            foreach ($result as &$pr_details) {
                $pr_numb = $pr_details['pr_no'];
                $sql = "SELECT * FROM dbo.Pr_item_details WHERE pr_no='$pr_numb'";
                $rowItems = $connection->prepare($sql);
                $rowItems->execute();
                $resultItems = $rowItems->fetchAll(PDO::FETCH_ASSOC);

                foreach ($resultItems as &$subitem) {
                    $prItems_id = $subitem["prItems_id"];

                    $sql = "SELECT * FROM dbo.Pr_subitem_details WHERE prItems_id='$prItems_id'";
                    $rowSubItems = $connection->prepare($sql);
                    $rowSubItems->execute();
                    $subitems = $rowSubItems->fetchAll(PDO::FETCH_ASSOC);

                    $subitem['pr_subitems'] = $subitems; // Replace 'Some value' with the desired value for 'pr_subitem'
                }


                $pr_details['items'] = $resultItems; // Replace 'Some value' with the desired value for 'pr_subitem'
            }

            
            // foreach($documents as $document) {
            //     echo $document['document_id'] . "<br>";
            // }
            return $result;
        }
    }


    public function updatePrPrintCounter($prno) {
        $connection = $this->openConnection();

        $sql = "UPDATE PR_details SET print_counter = print_counter + 1 WHERE pr_no=:prno";
        $rows = $connection->prepare($sql);
        $rows->execute(array(':prno' => $prno));
        $count = $rows->rowCount();
        if ($count == 0) {
            $array = array("status" => "0");
        } else {
            $array = array("status" => "1");
        }
        return $array;
    }

    public function viewPRHistory($prnum) {
        $connection = $this->openConnection();

        $sql = "SELECT * FROM Pr_status WHERE pr_no='$prnum' ORDER BY pr_datetime ASC";
        $rowItems = $connection->prepare($sql);
        $rowItems->execute();
        $documents = $rowItems->fetchAll(PDO::FETCH_ASSOC);

        $count = $rowItems->rowCount();

        if ($count == 0) {
            $arrayDocuments = array();
            return $arrayDocuments;
        } else {
            return $documents;
        }
    }
}