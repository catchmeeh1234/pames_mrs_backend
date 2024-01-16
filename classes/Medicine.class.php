<?php

    class Medicine extends Connect {

        public function __construct()
        {
            
        }

        public function loadMedicines() {
            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM medicines";
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function medicineInfo($medicine_id) {
            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM medicines WHERE medicine_id = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$medicine_id]);
            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function addMedicine($medicineInfo) {
            $obj = json_decode($medicineInfo, true);

            $connection = $this->openConnection();

            $medicine_generic_name =  trim($obj['medicine_generic_name']);
            $medicine_brand_name = trim($obj['medicine_brand_name']);
            $medicine_cost = trim($obj['medicine_cost']);
            
            //insert doctor info to database
            $sql = "INSERT INTO medicines (medicine_generic_name, medicine_brand_name, medicine_cost) VALUES (?, ?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$medicine_generic_name, $medicine_brand_name, $medicine_cost]);
            $count = $stmt->rowCount();
            $lastInsertedId = $connection->lastInsertId();

            if ($count == 0) {
                return ['status' => 'adding medicine failed'];
            } else {
                return ['status' => 'Medicine Added', 'medicine_id' => $lastInsertedId];
            }
        }

        public function editMedicineInfo($medicineInfo) {
            $obj = json_decode($medicineInfo, true);

            $connection = $this->openConnection();

            //hidden form front end
            $medicine_id = $obj['medicine_id'];
            // $original_doctor_name = $obj['original_doctor_name'];

            $medicine_generic_name =  trim($obj['medicine_generic_name']);
            $medicine_brand_name = trim($obj['medicine_brand_name']);
            $medicine_cost = trim($obj['medicine_cost']);

            // if ($original_doctor_name == $doctor_name) {
            //     $allow = true;
            // } else {
            //     $allow = false;
            // }

            //check if doctor's name already exists
            // $qry = "SELECT * FROM doctors WHERE doctor_name = ?";
            // $stmtDoctor = $connection->prepare($qry);
            // $stmtDoctor->execute([$doctor_name]);
            // $rowsDoctor = $stmtDoctor->fetchAll();
            // $countDoctor = $stmtDoctor->rowCount();

            // if ($countDoctor >= 1 && !$allow) {
                //return $arrayMessage = array('status' => 'Doctor' name already taken');
            // } else {
                $sql = "UPDATE medicines SET medicine_generic_name = ?, medicine_brand_name = ?, medicine_cost = ? WHERE medicine_id = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$medicine_generic_name, $medicine_brand_name, $medicine_cost, $medicine_id]);
                $count = $stmt->rowCount();
                
                if ($count != 1) {
                    //print_r($stmt->errorInfo());
                    return $arrayMessage = array('status' => 'Medicine Info update failed');
                    //exit("Failed1");
                } else {
                    return $arrayMessage = array('status' => 'Medicine Info updated successfully');
                }
            // }

        }

        public function updateMedicineQuantity($medicineInfo) {
            $obj = json_decode($medicineInfo, true);

            $connection = $this->openConnection();

            $medicine_id = $obj['medicine_id'];
            $medicine_generic_name = $obj['medicine_generic_name'];
            $medicine_brand_name = $obj['medicine_brand_name'];
            $stock_to_be_added = $obj['stock_to_be_added'];
            $medicine_quantity = $obj['medicine_quantity'];
            $remarks = $obj['remarks'];

            //change in quantity of medicine will put on the medicine history table
            $quantity = $medicine_quantity + $stock_to_be_added;

            //update medicine quantity
            $sql = "UPDATE medicines SET medicine_quantity = ? WHERE medicine_id = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$quantity, $medicine_id]);
            $count = $stmt->rowCount();

            if ($count != 1) {
                //print_r($stmt->errorInfo());
                return $arrayMessage = array('status' => 'Medicine Quantity update failed');
                //exit("Failed1");
            } else {
                //check if quantity is postive number
                if ($stock_to_be_added >= 0) {
                    $stock_to_be_added = '+' . $stock_to_be_added;
                }
                $currentdate = date("Y-m-d h:i:s A");

                //insert medicine history
                $sql = "INSERT INTO medicine_history (medicine_id, medicine_generic_name, medicine_brand_name, updated_quantity, remarks, datetime) 
                        VALUES (?,?,?,?,?, ?)";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$medicine_id, $medicine_generic_name, $medicine_brand_name, $stock_to_be_added, $remarks, $currentdate]);
                $count = $stmt->rowCount();

                if ($count == 1) {
                    return $arrayMessage = array('status' => 'Medicine quantity updated successfully');
                } else {
                    return $arrayMessage = array('status' => 'Adding medicine history failed');
                }
            }
        }

        public function loadMedicineHistory($medicine_id) {
            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM medicine_history WHERE medicine_id = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$medicine_id]);
            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }
    }
