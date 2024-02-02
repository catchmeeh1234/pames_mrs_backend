<?php
    class ScheduleCharges extends Connect {
        private $authInstance;

        public function __construct() {
            $this->authInstance = new Auth();
        }

        public function fetchScheduleChargesByAccountNo($accno) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * from ScheduleCharges where AccountNumber = ? and ActiveInactive = 1";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$accno]);
            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0 || !$stmt) {
                return [];
            } else {
                return $result;
            }
        }

        public function addCharges($chargeInfo) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $obj = json_decode($chargeInfo, true);

            $connection = $this->openConnection();

            $particular =  trim($obj['Particular']);
            
            //insert doctor info to database
            $sql = "INSERT INTO Charges (ChargeType, Category, Entry, Particular, Amount, ComputeRate) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$obj['ChargeType'], $obj['Category'], $obj['Entry'], $particular, $obj['Amount'], $obj['ComputeRate']]);
            $count = $stmt->rowCount();

            if ($count == 0) {
                //return $stmt->errorInfo();
                return ['status' => 'adding charges failed'];
            } else {
                return ['status' => 'Charges Added'];
            }
        }

        public function updateCharges($chargeInfo) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
            
            $obj = json_decode($chargeInfo, true);

            $connection = $this->openConnection();

            $particular =  trim($obj['Particular']);
            
            //insert doctor info to database
            $sql = "UPDATE Charges SET ChargeType=?, Category=?, Entry=?, Particular=?, Amount=?, ComputeRate=? WHERE ChargeID = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$obj['ChargeType'], $obj['Category'], $obj['Entry'], $particular, $obj['Amount'], $obj['ComputeRate'], $obj['ChargeID']]);
            $count = $stmt->rowCount();

            if ($count == 0) {
                //return $stmt->errorInfo();
                return ['status' => 'update charges failed'];
            } else {
                return ['status' => 'Charges Updated'];
            }
        }
    }
