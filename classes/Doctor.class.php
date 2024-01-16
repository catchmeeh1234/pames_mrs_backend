<?php

    class Doctor extends Connect {

        public function __construct()
        {
            
        }

        public function fetchDoctors() {
            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM doctors";
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

        public function doctorInfo($doctor_id) {
            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM doctors WHERE doctor_id = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$doctor_id]);
            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function addDoctor($doctorInfo) {
            $obj = json_decode($doctorInfo, true);

            $connection = $this->openConnection();

            $doctor_name =  trim($obj['doctor_name']);
            $doctor_address = trim($obj['doctor_address']);
            $doctor_type = trim($obj['doctor_type']);
            
            //insert doctor info to database
            $sql = "INSERT INTO doctors (doctor_name, doctor_address, doctor_type) VALUES (?, ?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$doctor_name, $doctor_address, $doctor_type]);
            $count = $stmt->rowCount();
            $lastInsertedId = $connection->lastInsertId();

            if ($count == 0) {
                return ['status' => 'adding doctor failed'];
            } else {
                return ['status' => 'Doctor Added', 'doctor_id' => $lastInsertedId];
            }
        }

        public function editDoctorInfo($doctorInfo) {
            $obj = json_decode($doctorInfo, true);

            $connection = $this->openConnection();

            //hidden form front end
            $doctor_id = $obj['doctor_id'];
            // $original_doctor_name = $obj['original_doctor_name'];

            $doctor_name =  trim($obj['doctor_name']);
            $doctor_address = trim($obj['doctor_address']);
            $doctor_type = trim($obj['doctor_type']);

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
                $sql = "UPDATE doctors SET doctor_name = ?, doctor_address = ?, doctor_type = ? WHERE doctor_id = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$doctor_name, $doctor_address, $doctor_type, $doctor_id]);
                $count = $stmt->rowCount();
                
                if ($count != 1) {
                    //print_r($stmt->errorInfo());
                    return $arrayMessage = array('status' => 'Doctor Info update failed');
                    //exit("Failed1");
                } else {
                    return $arrayMessage = array('status' => 'Doctor Info updated successfully');
                }
            // }

        }
    }
