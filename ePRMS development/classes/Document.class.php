<?php
//require_once "../includes/class-autoload.inc.php";
//header('Access-Control-Allow-Origin: *');

// require_once "./connect.inc.php";
// require_once "./logicnumbers.class.php";

class Document extends Connect
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

    public function fetchDocuments()
    {
        $connection = $this->openConnection();

        $sql = "SELECT * FROM documents ORDER BY dateUploaded DESC";
        $rowDocuments = $connection->prepare($sql);
        $rowDocuments->execute();
        $documents = $rowDocuments->fetchAll();
        $count = $rowDocuments->rowCount();
        if ($count == 0) {
            $arrayDocuments = array('status' => 'No Documents found');
            //echo json_encode($arrayDocuments);
            //return $arrayDocuments;
        } else {
            // foreach($documents as $document) {
            //     echo $document['document_id'] . "<br>";
            // }
            return $documents;
        }
    }

    

    public function addDocument($refno, $number, $file)
    {
        $this->refno = $refno;
        $this->number = $number;
        $this->file = $file;
        $currentdate = date("Y-m-d H:i:s");
        $target_file = $this->target_dir . basename($this->file["name"]);
        $this->fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $uploadStatus = $this->validateFile($this->file);
        $newFileName = $this->target_dir . $this->uniqueid . "." . $this->fileType;


        // Check if $uploadOk is set to 0 by an error
        if ($uploadStatus == 0) {
            echo "Sorry, your file was not uploaded.";
        // if everything is ok, try to upload file
        } else {
            $connection = $this->openConnection();
            if (move_uploaded_file($this->file["tmp_name"], $newFileName)) {
    
                $sql = "INSERT INTO documents (document_refNo, document_name, document_path, dateUploaded) VALUES(:document_refNo, :document_name, :document_path, :dateUploaded)";
                $stmt = $connection->prepare($sql);
                $stmt->execute(array(':document_refNo' => $refno, ':document_name' => $this->uniqueid .".". $this->fileType, ':document_path' => $newFileName, ':dateUploaded' => $currentdate));
                $count = $stmt->rowCount();
    
                if ($count == 1) {
                    $logic_numbers = new LogicNumbers();
                    $logicNumber = $logic_numbers->fetchLogicNumbers("document_referenceNumber");
                    $logicNumber++;
                    $updateLogicNumber = $connection->prepare("UPDATE logicnumbers SET number = :logic_number WHERE remarks = :remarks");
                    $updateLogicNumber->execute(['logic_number' => $logicNumber, ':remarks' => 'document_referenceNumber']);
                    $logicNumberCount = $updateLogicNumber->rowCount();
                    if ($logicNumberCount == 1) {
                        echo "The file ". htmlspecialchars( basename($this->file["name"])). " has been uploaded.";
                    }	
                }

            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        }

    }

    public function validateFile($file)
    {
        $uploadOk = 1;
        if (!isset($file)) {
            $uploadOk = 0;
            //exit('something went wrong');
        }
        $target_file = $this->target_dir . basename($file["name"]);
        $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));


        if ($fileType == "jpg" || $fileType == "png" || $fileType == "jpeg") {
            $this->target_dir = "files/images/";
        } else {
            $this->target_dir = "files/documents/";
        }
        return $uploadOk;
        
    }

    public function deleteDocument($id) {

        //fetch document by document id
        $documents = $this->selectOneDocument($id);

        foreach ($documents as $document) {
            $path = $document['document_path'];
            if(is_file($path)) 

            // Delete the given image
            unlink($path); 
        }
    
        //delete document by document id
        $connection = $this->openConnection();
        $sql = "DELETE FROM documents WHERE document_id = ?";
        $deleteDocument = $connection->prepare($sql);
        $deleteDocument->execute([$id]);
        $count = $deleteDocument->rowCount();
        if ($count != 0) {
            echo "Document Deleted";
        }     
        
    }

    public function deleteAllDocument() {
        
        $connection = $this->openConnection();
        $sql = "DELETE FROM documents";
        $deleteDocuments = $connection->prepare($sql);

        if ($deleteDocuments->execute()) {
            $images = glob('files/images/*'); 
            $documents = glob('files/documents/*'); 
    
            foreach($images as $image) {
        
                if(is_file($image)) 
                
                    // Delete the given image
                    unlink($image); 
            }
    
            foreach($documents as $document) {
        
                if(is_file($document)) 
                
                    // Delete the given document
                    unlink($document); 
            }
    
            $count = $deleteDocuments->rowCount();
    
            echo $count;
        
        }
    }

    public function selectOneDocument($id) {
        $connection = $this->openConnection();
        $sql = "SELECT * FROM documents WHERE document_id = ?";
        $selectDocument = $connection->prepare($sql);
        $selectDocument->execute([$id]);
        $documents = $selectDocument->fetchAll();

        if ($selectDocument->rowCount() == 1) {
            return $documents;
        } else {
            return false;
        }
    }

    public function editDocument($id, $file) {
        $uploadStatus = $this->validateFile($file);
        if ($uploadStatus == 0) {
            return false;
        } else {
            $connection = $this->openConnection();
            $currentdate = date("Y-m-d H:i:s");
            $documents = $this->selectOneDocument($id);

            $target_file = $this->target_dir . basename($file["name"]);
            $this->fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            $newFileName = $this->uniqueid . "." . $this->fileType;
            $file_path = $this->target_dir . $this->uniqueid . "." . $this->fileType;

            foreach($documents as $document) {
                //create a new file from the directory
                if (move_uploaded_file($file["tmp_name"], $file_path)) {
                    //delete from the directory
                    $path = $document['document_path'];
                    if(is_file($path)) 
                    unlink($path); 

                    $sql = "UPDATE documents SET document_name = ?, document_path = ?, dateUploaded = ?";
                    $updateDocument = $connection->prepare($sql);
                    $updateDocument->execute([$newFileName, $file_path, $currentdate]);

                    if ($updateDocument->rowCount() == 1) {
                    echo "Document Update Successfully";
                    } else {
                        echo "Document not updated";
                        return false;
                    }
                } else {
                    echo "Error updating document";
                }
            }
        }      
    }
}