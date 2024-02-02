<?php

    class ConsumerLedger extends Connect {
        private $authInstance;

        public function __construct() {
            $this->authInstance = new Auth();
        }

        public function addLedgerEntry($ledgerData, $connection=null) {

            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
                $connection = $this->openConnection();
            }

            $formatted_ledgerAmount = "";
            $formatted_ledgerDiscount = "";
            $formatted_ledgerBalance = "0.00";

            if ($ledgerData["ledgerAmount"] != "") {
                $formatted_ledgerAmount = number_format($ledgerData["ledgerAmount"], 2, '.', ',');
            }

            if ($ledgerData["ledgerDiscount"] != "") {
                $formatted_ledgerDiscount = number_format($ledgerData["ledgerDiscount"], 2, '.', ',');
            }

            if ($ledgerData["ledgerBalance"] != "") {
                $formatted_ledgerBalance = number_format($ledgerData["ledgerBalance"], 2, '.', ',');
            }

            //add entry to consumer ledger
            $sql = "INSERT into ConsumerLedger (ledgerAccountNo, ledgerRefNo, ledgerDate,
            ledgerParticulars, ledgerReading, ledgerConsumption, ledgerAmount, ledgerDiscount, ledgerBalance, ledgerCancelled) 
            values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $ledgerData["ledgerAccountNo"], $ledgerData["ledgerRefNo"], $ledgerData["ledgerDate"], 
                $ledgerData["ledgerParticulars"], $ledgerData["ledgerReading"], $ledgerData["ledgerConsumption"], 
                $formatted_ledgerAmount, $formatted_ledgerDiscount, $formatted_ledgerBalance, 'No'
            ]);

            if (!$stmt) {
                return ["status" => 0];
            } else {
                return ["status" => 1];
            }

        }

        public function viewConsumerLedgerData($account_no) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
            
            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM ConsumerLedger WHERE ledgerAccountNo = '$account_no' ORDER BY ledger_id asc";
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
    }