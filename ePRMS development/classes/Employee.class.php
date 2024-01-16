<?php
//require_once "../includes/class-autoload.inc.php";

// require_once "./connect.inc.php";
// require_once "./logicnumbers.class.php";

class Employee extends Connect
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

    public function selectEmployee($fullname)
    {
        $connection = $this->openConnection();

        $sql = "SELECT * FROM Pr_Employees WHERE full_name = '$fullname'";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        $count = $stmt->rowCount();
        if ($count == 0) {
            $arrayDocuments = array('status' => 'No Employee found');
            //echo json_encode($arrayDocuments);
            //return $arrayDocuments;
        } else {
            // foreach($documents as $document) {
            //     echo $document['document_id'] . "<br>";
            // }
            return $result;
        }
    }

    public function getEmployees($division) {
        $connection = $this->openConnection();

        if ($division == "ALL") {
            $sql = "SELECT full_name FROM Pr_Employees";
        } else {
            $sql = "SELECT full_name FROM dbo.Pr_Employees WHERE division = ?";
        }

        $stmt = $connection->prepare($sql);
        $stmt->execute([$division]);
        $emp = $stmt->fetchAll();
        $count = $stmt->rowCount();

        if ($count == 0) {
            //$arrayDocuments = array('status' => 'No division found');
            return array();
        } else {
            return $emp;
        }

    }
   
}