<?php
    class Charges extends Connect {
        public function __construct() {}

        public function fetchCharges() {
            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM Charges";
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

        public function fetchChargeInfo($particular) {
            $connection = $this->openConnection();
            //PDO query
            $sql = "SELECT * FROM Charges WHERE Particular = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$particular]);
            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function addCharges($chargeInfo) {
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
