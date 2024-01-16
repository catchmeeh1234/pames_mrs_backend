<?php

    class Patient extends Connect {

        public function __construct()
        {
            
        }

        public function fetchPatients() {
            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM patients";
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

        public function patientInfo($patient_id) {
            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM patients WHERE patient_id = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$patient_id]);
            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function addPatient($patientInfo) {
            $obj = json_decode($patientInfo, true);

            $connection = $this->openConnection();

            $patient_name =  trim($obj['patient_name']);
            $patient_address = trim($obj['patient_address']);
            
            //insert doctor info to database
            $sql = "INSERT INTO patients (patient_name, patient_address) VALUES (?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$patient_name, $patient_address]);
            $count = $stmt->rowCount();
            $lastInsertedId = $connection->lastInsertId();

            if ($count == 0) {
                return ['status' => 'adding doctor failed'];
            } else {
                return ['status' => 'Patient Added', 'patient_id' => $lastInsertedId];
            }
        }

        public function editPatientInfo($patientInfo) {
            $obj = json_decode($patientInfo, true);

            $connection = $this->openConnection();

            //hidden form front end
            $patient_id = $obj['patient_id'];

            $patient_name =  trim($obj['patient_name']);
            $patient_address = trim($obj['patient_address']);

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
                $sql = "UPDATE patients SET patient_name = ?, patient_address = ? WHERE patient_id = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$patient_name, $patient_address, $patient_id]);
                $count = $stmt->rowCount();
                
                if ($count != 1) {
                    //print_r($stmt->errorInfo());
                    return $arrayMessage = array('status' => 'Patient Info update failed');
                    //exit("Failed1");
                } else {
                    return $arrayMessage = array('status' => 'Patient Info updated successfully');
                }
            // }

        }
    }
