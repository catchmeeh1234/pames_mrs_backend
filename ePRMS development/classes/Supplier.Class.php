<?php
//require_once "../includes/class-autoload.inc.php";
//header('Access-Control-Allow-Origin: *');

// require_once "./connect.inc.php";
// require_once "./logicnumbers.class.php";

class Supplier extends Connect
{
    public function __construct()
    {
        
    }

    public function getSuppliers() {
        $connection = $this->openConnection();

        $sql = "SELECT * FROM Suppliers ORDER BY SupplierID DESC";
        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $suppliers = $stmt->fetchAll();
        $count = $stmt->rowCount();
        if ($count == 0) {
            $arrayDocuments = array('status' => 'No suppliers found');
            //echo json_encode($arrayDocuments);
            //return $arrayDocuments;
        } else {
            // foreach($documents as $document) {
            //     echo $document['document_id'] . "<br>";
            // }
            return $suppliers;
        }
    }
}