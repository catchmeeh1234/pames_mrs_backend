<?php
    //require_once 'Zone.class.php';

    class Consumer extends Connect {
        private $authInstance;
        private $zoneInstance;

        public function __construct() {
            $this->authInstance = new Auth(); 
            $this->zoneInstance = new Zone();
        }

        public function fetchConsumers($top) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $connection = $this->openConnection();

            //PDO query
            if ($top == "all") {
                $sql = "SELECT * FROM Consumers ORDER BY consumer_id asc";
            } else {   
                $sql = "SELECT TOP $top * FROM Consumers ORDER BY consumer_id asc";
            }

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

        public function searchConsumer($search, $zone, $status) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM Consumers WHERE 
                    (AccountNo LIKE '%$search%' OR 
                    Lastname LIKE '%$search%' OR 
                    Firstname LIKE '%$search%') AND
                    CustomerStatus LIKE '%$status%' AND
                    [Zone] LIKE '%$zone%'";
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return ['data'=> $search. " " .$status . " " .$zone];
            } else {
                return $result;
            }
        }

        public function fetchConsumerInfo($consumer_id) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM Consumers WHERE Consumer_id = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$consumer_id]);
            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function fetchConsumerInfoByAccNo($accno) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM Consumers WHERE AccountNo = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$accno]);
            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result[0];
            }
        }

        public function fetchConsumerCharges($account_no) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM ScheduleCharges WHERE AccountNumber = ? ORDER BY ActiveInactive desc, ScheduleChargesID desc";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$account_no]);
            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function addScheduleCharge($scheduleChargeInfo) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $obj = json_decode($scheduleChargeInfo, true);

            $connection = $this->openConnection();

            //this is an object
            $charge =  $obj['Charge'];
            $chargeID = $charge["ChargeID"];
            $chargeType = $charge["ChargeType"];
            $chargeRate = $charge["ComputeRate"];
            $category = $charge["Category"];
            $entry = $charge["Entry"];
            $particular = $charge["Particular"];
            $amount = $charge["Amount"];

            //convert to 1 or 0
            $isActive = $obj['IsActive'];

            //convert this values to Yes or No
            $isRecurring = $obj['IsRecurring'];

            if ($isRecurring == true) {
                $isRecurringString = "Yes";
                $monthh = "0";

            } else {
                $isRecurringString = "No";
                $monthh = "1";
            }

            //add 1 to billing month
            $billingMonth = $obj['BillingMonth'] + 1;
            $billingYear = $obj['BillingYear'];

            $remarks = trim($obj['Remarks']);
            $accountNumber = trim($obj['AccountNumber']);
            $createdBy = trim($obj['CreatedBy']);
            $currentDate = date("Y-m-d");


            //ADD SCHEDULE CHARGES TO DATABASE
            $sql = "INSERT INTO ScheduleCharges (ChargeID, ChargeType, ChargeRate, Category
            ,Entry
            ,Particular
            ,AccountNumber
            ,Amount
            ,Remarks
            ,Recurring
            ,Monthh
            ,BillingMonth
            ,BillingYear
            ,DateCreated
            ,ActiveInactive
            ,CreatedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $chargeID, $chargeType, $chargeRate, $category, 
                $entry, $particular, $accountNumber, $amount,
                $remarks, $isRecurringString, $monthh, $billingMonth, 
                $billingYear, $currentDate, $isActive, $createdBy,
            ]);
            $count = $stmt->rowCount();

            if ($count == 0) {
                return ['status' => 'Adding Failed'];
                //return $stmt->errorInfo();
            } else {
                return ['status' => 'Schedule Charge Added', 'echo' => $isRecurring];
            }
        }

        public function editScheduleCharge($scheduleChargeInfo) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $obj = json_decode($scheduleChargeInfo, true);

            $connection = $this->openConnection();

            $scheduleChargesID =  $obj['ScheduleChargesID'];

            //this is an object
            $charge =  $obj['Charge'];
            $chargeID = $charge["ChargeID"];
            $chargeType = $charge["ChargeType"];
            $chargeRate = $charge["ComputeRate"];
            $category = $charge["Category"];
            $entry = $charge["Entry"];
            $particular = $charge["Particular"];
            $amount = $charge["Amount"];

            //convert to 1 or 0
            $isActive = $obj['IsActive'];

            //convert this values to Yes or No
            $isRecurring = $obj['IsRecurring'];

            if ($isRecurring == true) {
                $isRecurringString = "Yes";
                $monthh = "0";

            } else {
                $isRecurringString = "No";
                $monthh = "1";
            }

            //add 1 to billing month
            $billingMonth = $obj['BillingMonth'];
            $billingYear = $obj['BillingYear'];

            $remarks = trim($obj['Remarks']);

            $sql = "UPDATE ScheduleCharges SET 
                    ChargeID = ?, ChargeType = ?, ChargeRate = ?, 
                    Category = ?, Entry = ?, Particular = ?, 
                    Amount = ?, ActiveInactive = ?, Recurring = ?, 
                    BillingMonth = ?, BillingYear = ?, Remarks = ?, Monthh = ?
                    WHERE ScheduleChargesID = ?";

            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $chargeID, $chargeType, $chargeRate,
                $category, $entry, $particular,
                $amount, $isActive, $isRecurringString, 
                $billingMonth, $billingYear, $remarks, $monthh, $scheduleChargesID
            ]);
            $count = $stmt->rowCount();

            if ($count == 0) {
                //return ['status' => 'Updating Failed'];
                return $stmt->errorInfo();
            } else {
                return ['status' => 'Schedule Charge Updated', 'echo' => $isRecurring];
            }
        }

        public function addConsumer($consumerInfo) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $connection = $this->openConnection();

            try {
                $connection->beginTransaction();

                $obj = json_decode($consumerInfo, true);
                $account_no =  $obj['AccountNo'];

                $this->validateAccountNo($account_no, $connection);

                $lastname = trim($obj['Lastname']);
                $firstname = trim($obj['Firstname']);
                $middlename = trim($obj['Middlename']);
                $address = trim($obj['ServiceAddress']);
                $landmark = trim($obj['LandMark']);
                $contactNo = trim($obj['ContactNo']);

                $meterNo = trim($obj['MeterNo']);
                $readingSeqNo = trim($obj['ReadingSeqNo']);
                $zone = $obj['ZoneName'];
                $type = $obj['RateSchedule'];
                $dateCreated = $obj['dateCreated'];
                $dateInstalled = $obj['dateInstalled'];

                $customerstatus = $obj['CustomerStatus'];
                $isSenior = $obj['IsSenior'];

                $username = $obj['Username'];

                if ($isSenior == 1) {
                    $newIsSenior = "Yes";
                } else {
                    $newIsSenior = "No";
                }

                
                //insert doctor info to database
                $sql = "INSERT INTO Consumers 
                        (
                            AccountNo, Lastname, Firstname, Middlename, ServiceAddress, 
                            LandMark, ContactNo, MeterNo, ReadingSeqNo, 
                            Zone, RateSchedule, DateCreated, DateInstalled, CustomerStatus, 
                            IsSenior, InstalledBy, CreatedBy, LastMeterReading, Averagee
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $connection->prepare($sql);
                $stmt->execute([
                    $account_no, $lastname, $firstname, $middlename, 
                    $address, $landmark, $contactNo, $meterNo,
                    $readingSeqNo, $zone, $type, $dateCreated, 
                    $dateInstalled, $customerstatus, $newIsSenior,
                    $username, $username, 0, 0
                ]);
                $count = $stmt->rowCount();
                $lastInsertedId = $connection->lastInsertId();

                if (!$stmt || $count <= 0) {
                    //return ['status' => 'Adding Failed', 'data' => $stmt->errorInfo()[2]];
                    throw new Exception("Adding account failed: " . $stmt->errorInfo()[2]);
                } 
                
                //update zone's last number
                $this->zoneInstance->updateZoneLastNumber($connection, $zone);

                $connection->commit();
                $this->closeConnection();
                return ['status' => 'Consumer Added'];
                
            } catch (Exception $e) {
                $connection->rollBack();
                return ["status" => $e->getMessage()];
            }

        }

        public function updateConsumerInfo($consumerInfo) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $connection = $this->openConnection();

            $obj = json_decode($consumerInfo, true);

            $lastname =  trim($obj['Lastname']);
            $firstname = trim($obj['Firstname']); 
            $middlename = trim($obj['Middlename']);

            $sql = "UPDATE Consumers SET 
                    Lastname = ?, Firstname = ?, Middlename = ?, ServiceAddress = ?, ContactNo = ?, 
                    MeterNo = ?, ReadingSeqNo = ?, LandMark = ?, Zone = ?, RateSchedule = ?, IsSenior = ?, DateInstalled = ?, CreatedBy = ? WHERE AccountNo = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $lastname, $firstname, $middlename, $obj['ServiceAddress'], $obj['ContactNo'], 
                $obj['MeterNo'], $obj['ReadingSeqNo'], $obj['LandMark'], $obj['ZoneName'], $obj['RateSchedule'], $obj['IsSenior'], $obj['dateInstalled'], $obj['Username'], $obj['AccountNo']
            ]);
            $count = $stmt->rowCount();

            if ($count <= 0 || !$stmt) {
                //print_r($stmt->errorInfo());
                return $arrayMessage = array('status' => 'Consumer Info update failed');
                //exit("Failed1");
            } else {
                return $arrayMessage = array('status' => 'Consumer Info updated successfully');
            }
        }

        public function consumerStatuses() {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM CustomerStatus";
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

        public function consumerSummary() {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $consumerSummary = [];
            $connection = $this->openConnection();

            $queryArray = array(
                array(
                    "name" => "TotalCustomer",
                    "qry" => ""
                ),
                array(
                    "name" => "TotalActive",
                    "qry" => " WHERE CustomerStatus = 'Active'"
                ),
                array(
                    "name" => "TotalDisconnected",
                    "qry" => " WHERE CustomerStatus = 'Disconnected'"
                )
            );
            
            foreach ($queryArray as $val) {
                $sql = "SELECT * FROM Consumers" .$val["qry"];
                $stmt = $connection->prepare($sql);
                $stmt->execute();
                $result = $stmt->fetchAll();
                $count = $stmt->rowCount();

                //$consumerSummary[$val->Name] = $count;
                $consumerSummary[$val['name']] = $count;
            }
            return $consumerSummary;
        }

        public function validateAccountNo($accno, $connection=null) {
            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
                $connection = $this->openConnection();
            }

            $sql = "SELECT AccountNo FROM Consumers WHERE AccountNo = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$accno]);
            $stmt->fetchAll();

            if (!$stmt || $stmt->rowCount() >= 1) {
                throw new Exception("Account number already exist");
            }
        }

        public function viewActiveConsumers($zone, $connection=null) {
            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
                $connection = $this->openConnection();
            }

            //check if bill is already created by zone
            $sql = "SELECT * from Consumers where [Zone] = '$zone' and CustomerStatus = 'Active' order by ReadingSeqNo asc";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$zone]);
            $result = $stmt->fetchAll();

            if (!$stmt || $stmt->rowCount() < 0) {
                //throw new Exception("No Active Consumer on the selected zone");
                return [];
            } else {
                return $result;
            }

        }

        public function updateAccountStatus($accountStatusInfo, $connection=null) {
            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
                $connection = $this->openConnection();
            }

            $obj = json_decode($accountStatusInfo, true);
            try {
                $connection->beginTransaction();

                $customerStatus = $obj['customerStatus'];
                $dateLastDisconnected = $obj["date"];
                $accountNo = $obj["accountNo"];

                $this->addAccountStatus($obj, $connection);
                $this->addAccountHistory($obj, $connection);
                $this->updateLastDisconnectedAndStatus($dateLastDisconnected, $customerStatus, $accountNo, $connection);

                $connection->commit();
                $this->closeConnection();
                return ['status' => 'Account status updated'];
                
            } catch (Exception $e) {
                $connection->rollBack();
                return ["status" => $e->getMessage()];
            }

        }

        public function addAccountStatus($obj, $connection=null) {
            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
                $connection = $this->openConnection();
            }

            $date = $obj["date"];
            $accountNo = $obj["accountNo"];
            $status = $obj["customerStatus"];
            $remarks = $obj["remarks"];
            $updatedBy = $obj["username"];

            if ($status === "Disconnected") {
                $discType = $obj["discType"];
                $meterStatus = $obj["meterStatus"];
                $lastReading = $obj["lastReading"];
                $performedBy = $obj["performedBy"];

                $sql = "INSERT INTO AccountStatus 
                (
                    StatusDate,AccountNo,Status,StatusType,MeterStatus,
                    LastReading,Remarks,DiscBy,UpdatedBy
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $tempArray = [
                    $date, $accountNo, $status, $discType, $meterStatus,
                    $lastReading, $remarks, $performedBy, $updatedBy
                ];
            } else {
                $sql = "INSERT INTO AccountStatus 
                (
                    StatusDate,AccountNo,Status,Remarks,UpdatedBy
                )
                VALUES (?, ?, ?, ?, ?)";
                $tempArray = [
                    $date, $accountNo, $status, $remarks, $updatedBy
                ];
            }

            $stmt = $connection->prepare($sql);
            $stmt->execute($tempArray);
            $count = $stmt->rowCount();
            if ($count <= 0) {
                throw new Exception("adding of account status failed");
            }
        }

        public function addAccountHistory($obj, $connection=null) {
            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
                $connection = $this->openConnection();
            }

            $currentDate = date("Y-m-d");
            $accountNo = $obj["accountNo"];
            $category = "status";
            $customerStatus = $obj["customerStatus"];
            $remarks = $obj["remarks"];
            $createdBy = $obj["username"];

            $sql = "INSERT INTO AccountHistory 
            (
                historyDate, historyAccountNo, historyCategory, historyName, historyRemarks, historyCreatedBy
            )
            VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $currentDate, $accountNo, $category, $customerStatus, $remarks, $createdBy
            ]);
            $count = $stmt->rowCount();
            if ($count <= 0) {
                throw new Exception("adding of account history failed");
            }
        }

        public function updateLastDisconnectedAndStatus($dateLastDisconnected, $customerStatus, $accountNo, $connection=null) {
            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
                $connection = $this->openConnection();
            }

            if ($customerStatus === "Disconnected") {
                $sql = "UPDATE Consumers set DateLastDisconnected = ?, CustomerStatus = ? where AccountNo = ?";
                $tempArray = [$dateLastDisconnected, $customerStatus, $accountNo];
            } else {
                $sql = "UPDATE Consumers set CustomerStatus = ? where AccountNo = ?";
                $tempArray = [$customerStatus, $accountNo];
            }
            $stmt = $connection->prepare($sql);
            $stmt->execute($tempArray);
            if (!$stmt || $stmt->rowCount() <= 0) {
                throw new Exception("updating of consumer status and date failed"); 
            }
        }

        public function fetchAccountStatusTable($accountNo) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM AccountStatus WHERE AccountNo = ? ORDER BY ID DESC";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$accountNo]);
            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function updateAdvancePayment($data, $connection=null) {
            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
                
                $connection = $this->openConnection();
            }

            $sql = "UPDATE Consumers set AdvancePayment = ? WHERE AccountNo = ?";

            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $data['AdvancePayment'], $data['AccountNo']
            ]);
            if (!$stmt) {
                return ["status" => 0];
            } else {
                return ["status" => 1];
            }
        }
    }
