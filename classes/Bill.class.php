<?php
    require_once 'LogicNumber.class.php';
    require_once 'Consumer.class.php';
    require_once 'ScheduleCharges.class.php';
    require_once 'Discount.class.php';

    require __DIR__ . '/../vendor/autoload.php';
    use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
    use Mike42\Escpos\Printer;

    class Bill extends Connect {

        public function __construct() {}

        public function fetchBills($accno) {
            $connection = $this->openConnection();

            if ($accno === "All") {
                $sql = "SELECT * FROM Bills";
                $stmt = $connection->prepare($sql);
                $stmt->execute();
            } else {
                $sql = "SELECT * FROM Bills WHERE AccountNumber = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$accno]);
            }

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }
        

        public function fetchBillByBillno($billno) {
            $connection = $this->openConnection();

            $sql = "SELECT * FROM Bills WHERE BillNo = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$billno]);

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function fetchBillCharges($billno, $connection=null) {
            if ($connection == null) {
                $connection = $this->openConnection();
            }

            if ($billno === "All") {
                $sql = "SELECT * FROM BillCharges";
                $stmt = $connection->prepare($sql);
                $stmt->execute();
            } else {
                $sql = "SELECT * FROM BillCharges WHERE BillNumber = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$billno]);
            }

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function fetchPendingBills($connection=null) {
            if ($connection == null) {
                $connection = $this->openConnection();
            }

            $sql = "SELECT * FROM Bills WHERE BillStatus= ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute(['Pending']);

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function prepareBills($zones, $billingMonth, $meterReader, $createdBy) {
            $connection = $this->openConnection();
            $preparedBills = [];

            try {
                $currentdate = date("Y-m-d");

                $connection->beginTransaction();

                foreach ($zones as $zone) {
                    //CHECK IF THERE ARE PENDING BILLS
                    $response = $this->checkIfPendingBillByZone($connection, $zone);
                    if ($response == 0) {
                        throw new Exception("$zone has a pending bill");
                    }

                    //SELECT ALL ACTIVE CONSUMERS FROM THE SPECIFIC ZONE
                    $consumerInstance = new Consumer();
                    $consumers = $consumerInstance->viewActiveConsumers($connection, $zone);

                    //CHECK IF THERE IS AN EXISTING BILL FOR THAT PARTICULAR MONTH FOR THE PARTICULAR CONSUMER
                    //SKIP THAT CONSUMER IF HE ALREADY HAVE ONE
                    foreach ($consumers as $consumer) {
                        $firstname = $consumer['Firstname'];
                        $middlename = $consumer['Middlename'];
                        $lastname = $consumer['Lastname'];
                        $customerName = "$firstname $middlename $lastname"; 

                        if ($consumer["LasReadingDate"] == "" || $consumer["LasReadingDate"] == null) {
                            $newDateFrom = $consumer["DateInstalled"];
                        } else {
                            $newDateFrom = $consumer["LasReadingDate"];
                        }

                        $response = $this->checkIfBillExists($connection, $billingMonth, $consumer["AccountNo"]);
                        if ($response == 1) {
                            $billInfo = [
                                "AccountNumber" => $consumer["AccountNo"],
                                "CustomerName" => $customerName,
                                "CustomerAddress" => $consumer["ServiceAddress"],
                                "DateFrom" => $newDateFrom,
                                "LastMeterReading" => $consumer["LastMeterReading"],
                                //"ReadingDate" => "",
                                //"DueDate" => "",
                                "Reading" => 0,
                                "Consumption" => 0,
                                "BillingMonth" => $billingMonth,
                                "BillStatus" => "Pending",
                                "RateSchedule" => $consumer["RateSchedule"],
                                "Zone" => $zone,
                                "IsSenior" => $consumer["IsSenior"],
                                "AmountDue" => 0,
                                "IsPaid" => "No",
                                "MeterReader" => $meterReader,
                                "AverageCons" => $consumer["Averagee"],
                                "SeniorDiscount" => 0,
                                "MeterNo" => $consumer["MeterNo"],
                                //"DiscDate" => "",
                                "dateCreated" => $currentdate,
                                "createdBy" => $createdBy,

                            ];

                            array_push($preparedBills, $billInfo);
                        }
                    }

                }


                $connection->commit();
                $this->closeConnection();

                return ["status" => "Bills Prepared", "count" => count($preparedBills), "result"=> $preparedBills];

            } catch (Exception $e) {
                // Rollback the transaction on error
                $connection->rollBack();
                return ["status" => $e->getMessage()];
            }
        }

        public function createBills($preparedBills, $billingMonth, $zones) {
            $connection = $this->openConnection();

            try {
                $connection->beginTransaction();

                //Action
                $action = "Add";
                //FETCH LOGIC NUMBER
                $billno = intval($this->getLogicNumber("BillNo")[0]["number"]);
                $lastBillNo = $billno;

                $obj = json_decode($preparedBills, true);

                if ($obj === null) {
                    throw new Exception("Prepared bills is null");    
                }

                foreach ($obj as $billInfo) {
                    $accno = $billInfo["AccountNumber"];
 
                    $responseBillInfo = $this->addBillsWithEmptyReading($connection, $billInfo, $billno);
                    if ($responseBillInfo["status"] == 0) {
                        throw new Exception("Adding Bills with empty reading failed: " . $responseBillInfo["message"]);
                    }

                    //get active schedule charges on a specific account
                    $scheduleChargesInstance = new ScheduleCharges();
                    $scheduleCharges = $scheduleChargesInstance->fetchScheduleChargesByAccountNo($accno);
                    if (count($scheduleCharges) >= 1) {
                        //LOOP THROUGH EACH SCHEDULE CHARGES
                        foreach ($scheduleCharges as $scheduleCharge) {
                            //CHECK IF SCHEDULE CHARGES IS RECURRING
                            $IsRecurring = $scheduleCharge["Recurring"];

                            $billInfo['BillingMonth'] = $billingMonth;
                            if ($IsRecurring == "Yes") {
                                $response = $this->addOrUpdateBillCharges($connection, $billInfo, $scheduleCharge, $action);
                                if ($response["status"] == 0) {
                                    throw new Exception("Adding Bill charges with empty reading and recurring is yes failed");
                                }
                            } else {
                                $scheduleChargeMonth = date("F", mktime(0, 0, 0, $scheduleCharge['BillingMonth'], 1));
                                $scheduleChargeBillingMonth = $scheduleChargeMonth . " " . $scheduleCharge["BillingYear"];
                                if ($billingMonth == $scheduleChargeBillingMonth) {
                                    $response = $this->addOrUpdateBillCharges($connection, $billInfo, $scheduleCharge, $action);
                                    if ($response["status"] == 0) {
                                        throw new Exception("Adding Bill charges with empty reading and recurring is no failed");
                                    }
                                }     
                            }
                        }
                    }
                    //increment bill number by 1
                    $billno++;
                    //$reponse = $this->addToBillsTemp($connection, $responseBillInfo["result"], $zones);
                }

                // if ($reponse["status"] == 0) {
                //     throw new Exception("Adding Bills to billstemp failed: " . $reponse["result"]);
                // }

                    //update or billnumber
                $newBillNo = $lastBillNo + count($obj);
                $logicNumberInstance = new LogicNumber();
                $count = $logicNumberInstance->updateLogicNumber("BillNo", $newBillNo);

                if ($count <= 0) {
                    throw new Exception("Updating tbllogicnumbers failed");
                }

                $totalBillsCreated = $billno - $lastBillNo;

                $connection->commit();
                $this->closeConnection();
                return ["status" => "Bills Created", "count" => $totalBillsCreated, "result" => $newBillNo];

            } catch (Exception $e) {
                // Rollback the transaction on error
                $connection->rollBack();
                return ["status" => $e->getMessage()];
            }
        }

        public function createBill($billInfo) {
            $connection = $this->openConnection();

            try {
                $connection->beginTransaction();

                //Action
                $action = "Add";
                $obj = json_decode($billInfo, true);

                $AccountNo =  trim($obj['AccountNumber']);
                $billingMonth = $obj["Month"] . " ". $obj["Year"];
                $billno = intval($this->getLogicNumber("BillNo")[0]["number"]);
                //$billInfo["BillingMonth"] = $billingMonth;

                //check if bill exists
                $response = $this->checkIfBillExists($connection, $billingMonth, $AccountNo);
                if ($response == 0) {
                    throw new Exception("Bill for this month already exist");
                }

                //check if bill number has duplicate
                $this->checkIfBillNumberExists($connection, $billno);

                //check if there is a pending bill for the current account
                $this->checkIfPendingBillByAccountNo($connection, $AccountNo);

               
                $response = $this->addOrUpdateBillWithReading($connection, $obj, "Add");
                $resultBill = $response["result"];

                if ($response["status"] == 0) {
                    throw new Exception("Adding Bill failed");
                }
                
                $billCharges = $obj["Charges"];

                foreach ($billCharges as $billCharge) {
                    $response = $this->addOrUpdateBillCharges($connection, $obj, $billCharge, $action);

                    if ($response["status"] === 0) {
                        throw new Exception("Adding Bill Charges failed" . $response['test']);
                    }
                }

                //update logic number
                $logicNumberInstance = new LogicNumber();

                $count = $logicNumberInstance->updateLogicNumber("BillNo", 1);

                if ($count <= 0) {
                    throw new Exception("Updating tbllogicnumbers failed");
                }

                // Commit the transaction
                $connection->commit();
                $this->closeConnection();
                return ["status" => "Bill Created" , "billno"=> $billno];

            } catch (Exception $e) {
                // Rollback the transaction on error
                $connection->rollBack();
                return ["status" => $e->getMessage()];
            }
        }

        public function updateBill($billInfo) {
            $connection = $this->openConnection();

            try {
                $connection->beginTransaction();

                $obj = json_decode($billInfo, true);
                $action = "Update";
                $AccountNo =  trim($obj['AccountNo']);
                $billingMonth = $obj["Month"] . " ". $obj["Year"];

                $response = $this->addOrUpdateBillWithReading($connection, $obj, $action);
                $resultBill = $response["result"];

                if ($response["status"] == 0) {
                    throw new Exception("Updating Bill failed");
                }
                
                $billCharges = $obj["Charges"];

                foreach ($billCharges as $billCharge) {
                    $response = $this->addOrUpdateBillCharges($connection, $obj, $billCharge, $action);

                    if ($response["status"] === 0) {
                        throw new Exception("Updating Bill Charges failed" . $response['test']);
                    }
                }

                // Commit the transaction
                $connection->commit();
                $this->closeConnection();
                return ["status" => "Bill Updated", "billno"=> $obj["BillNo"], "message" => $billCharges];

            } catch (Exception $e) {
                // Rollback the transaction on error
                $connection->rollBack();
                return ["status" => $e->getMessage()];
            }
        }

        public function postBill($billno, $accno, $postedBy) {
            $connection = $this->openConnection();

            try {
                $connection->beginTransaction();

                //fetch bill by bill no
                $billInfo = $this->fetchBillByBillno($billno)[0];

                //update bill status on Bills table
                $responseBill = $this->updateBillStatus($connection, "Bills", 'Posted', $billno, $postedBy);
                if ($responseBill == 0) {
                    throw new Exception("error updating bills' bill status");
                }

                //check average cons base on last 3 bills
                
                $sql = "SELECT AVG(Consumption) AS AverageConsumption
                FROM (
                    SELECT TOP 3 Consumption
                    FROM Bills
                    WHERE BillStatus = ? AND AccountNumber = ?
                    ORDER BY BillNo DESC
                ) AS Top3Bills";
                $stmt = $connection->prepare($sql);
                $stmt->execute([
                    "Posted", $accno
                ]);
                $result = $stmt->fetchAll();
                $newaverage = 0;
                foreach ($result as $average) {
                    $newaverage += $average["AverageConsumption"];
                }

                //get previous bill no
                $prevBillNo = $this->getPreviousBillNo($connection, $accno);

                //update consumer's table
                $this->updateLastReadingDate($connection, $prevBillNo, $accno);

                //update last meter reading, avarageem, lastbill
                $sql = "update Consumers set Averagee = ?, LastMeterReading = ?, LastBill = ? where AccountNo = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([
                    $newaverage, $billInfo["Reading"], $billInfo["BillNo"], $billInfo["AccountNumber"]
                ]);

                if ($stmt->rowCount() <= 0) {
                    throw new Exception("Updating Consumer's Table failed");
                }

                //fetch unpaid bills
                $billTotal = 0;
                $billChargesTotal = 0;
                
                $data = [
                    "AccountNumber" => $billInfo['AccountNumber'],
                    "IsPaid" => "No",
                    "BillStatus" => "Posted",
                    "IsCollectionCreated" => "Yes",
                ];
                
                $unpaidBills = $this->fetchUnpaidBills($data, $connection);
                foreach ($unpaidBills as $unpaidBill) {
                    $total =  ($unpaidBill["AmountDue"] + $unpaidBill["Adjustment"]) - ($unpaidBill["AdvancePayment"] + $unpaidBill["SeniorDiscount"]);
                    $billTotal += $total;
                }

                //fetch unpaid bill charges
                $data = [
                    "AccountNumber" => $billInfo['AccountNumber'],
                    "IsPaid" => "No",
                    "BillStatus" => "Posted",
                    "IsCollectionCreated" => "Yes",
                ];
                $unpaidBillCharges = $this->fetchUnpaidBillCharges($data, $connection);
                foreach ($unpaidBillCharges as $unpaidBillCharge) {
                    $total =  $unpaidBillCharge["Amount"];
                    $billChargesTotal += $total;
                }

                //sum unpaid bills
                $totalAmountDue = $billTotal + $billChargesTotal;
                
                //compute discount
                //$totalDiscount = $this->computeDiscounts($billInfo["SeniorDiscount"]);
                if ($billInfo["SeniorDiscount"] > 0) {
                    $newTotalDiscount = $billInfo["SeniorDiscount"];
                } else {
                    $newTotalDiscount = "";
                }

                //add entry to ledger (bill)
                $ledgerData = [
                    "ledgerAccountNo" => $billInfo['AccountNumber'],
                    "ledgerRefNo" => $billInfo["BillNo"],
                    "ledgerDate" => $billInfo["dateCreated"],
                    "ledgerParticulars" => "Billing",
                    "ledgerReading" => $billInfo["Reading"],
                    "ledgerConsumption" => $billInfo["Consumption"],
                    "ledgerAmount" => $billInfo["AmountDue"],
                    "ledgerDiscount" => $newTotalDiscount,
                    "ledgerBalance" => $totalAmountDue,
                ];

                $consumerLedgerInstance = new ConsumerLedger();
                $consumerLedger = $consumerLedgerInstance->addLedgerEntry($ledgerData, $connection);

                if ($consumerLedger['status'] === 0) {
                    throw new Exception("error adding bill to consumer's ledger");
                }

                //update bill charges status
                $bill_no = $billInfo['BillNo'];
                $response = $this->updateBillChargesStatus($bill_no, $connection);
                if ($response["status"] === 0) {
                    throw new Exception("error updating bill charges status");
                }


                //add entry to ledger (bill charges)
                // loop through bill charges
                $billCharges = $this->fetchBillCharges($billInfo["BillNo"], $connection);
                foreach ($billCharges as $billCharge) {
                    $totalAmountDue += $billCharge["Amount"];

                    $ledgerData = [
                        "ledgerAccountNo" => $billInfo['AccountNumber'],
                        "ledgerRefNo" => $billInfo["BillNo"],
                        "ledgerDate" => $billInfo["dateCreated"],
                        "ledgerParticulars" => $billCharge["Particulars"],
                        "ledgerReading" => "",
                        "ledgerConsumption" => "",
                        "ledgerAmount" => $billCharge["Amount"],
                        "ledgerDiscount" => "",
                        "ledgerBalance" => $totalAmountDue,
                    ];

                    $consumerLedger = $consumerLedgerInstance->addLedgerEntry($ledgerData, $connection);

                    if ($consumerLedger['status'] === 0) {
                        throw new Exception("error adding bill charges entry to consumer's ledger");
                    }

                    //update schedule charges set non recurring to inactive
                    $sql = "UPDATE ScheduleCharges set ActiveInactive = 0 where Recurring = 'No' 
                            and ScheduleChargesID = ?";
                    $stmt = $connection->prepare($sql);
                    $stmt->execute([
                        $billCharge["ScheduleChargesID"]
                    ]);

                    if (!$stmt) {
                        throw new Exception("error updating schedule charges " . $billCharge["ScheduleChargesID"]);
                    }
                }
              

                $connection->commit();

                $this->closeConnection();

                return ["status" => "Bill Posted", "result"=> $billInfo["BillNo"]];
            } catch (Exception $e) {
                $connection->rollBack();
                return ["status" => $e->getMessage()];
            }
        }

        public function cancelBill($billInfo) {
            $connection = $this->openConnection();

            try {
                $connection->beginTransaction();

                $obj = json_decode($billInfo, true);

                //$BillStatus = $obj['BillStatus'];
                //$billno = $obj['BillNo'];
                $BillStatus = $obj['BillStatus'];
                $IsPaid = $obj['isPaid'];

                $billno = $obj['BillNo'];
                $accno = $obj['AccountNumber'];
                $referenceNo =  $obj['ReferenceNo'];
                $date =  $obj['CurrentDate'];
                $amount =  $obj['AmountDue'];
                $seniorDiscount =  $obj['SeniorDiscount'];


                $this->addCanceledBillRecord($connection, $billInfo);
                $responseBill = $this->updateBillStatus($connection, "Bills", "Cancelled", $billno);
                if ($responseBill == 0) {
                    throw new Exception("error updating bills' bill status");
                }
                $responseBillCharge = $this->updateBillStatus($connection, "BillCharges", "Cancelled", $billno);
                if ($responseBillCharge == 0) {
                    throw new Exception("error updating billcharges' bill status");
                }

                if ($BillStatus == 'Pending' && $IsPaid === 'No') {   
                    //OK NA TO
                } else if ($BillStatus == 'Posted' && $IsPaid === 'No') {

                    //Get Previous bill number
                    $prevBillNo = $this->getPreviousBillNo($connection, $accno);
                    //revert LastReadingDate to previous
                    $this->updateLastReadingDate($connection, $prevBillNo, $accno);

                    //compute total amount due and discounts
                    $totalDiscount = $this->computeDiscounts($seniorDiscount);
                    $totalAmountDue = number_format($amount + $totalDiscount, 2);
                    //create a ledger data object
                    $ledgerData = [
                        "ledgerAccountNo" => $accno,
                        "ledgerRefNo" => $referenceNo,
                        "ledgerDate" => $date,
                        "ledgerParticulars" => "Cancelled Bill($billno)",
                        "ledgerReading" => "",
                        "ledgerConsumption" => "",
                        "ledgerAmount" => "",
                        "ledgerDiscount" => $amount,
                        "ledgerBalance" => $totalAmountDue,
                    ];

                    //ADD DATA TO CONSUMER LEDGER
                    $consumerLedgerInstance = new ConsumerLedger();
                    $consumerLedger = $consumerLedgerInstance->addLedgerEntry($ledgerData, $connection);

                    if ($consumerLedger['status'] === 0) {
                        throw new Exception("error adding bill to consumer's ledger");
                    }

                } elseif ($BillStatus == 'Posted' && $IsPaid === "Yes") {
                    //Get Previous bill number
                    $prevBillNo = $this->getPreviousBillNo($connection, $accno);
                    //revert LastReadingDate to previous
                    $this->updateLastReadingDate($connection, $prevBillNo, $accno);

                    //TODO
                }
                 else {
                    throw new Exception("Bill status is not pending nor posted");
                }

                $logicNumberInstance = new LogicNumber();
                $count = $logicNumberInstance->updateLogicNumber("CancelBill", 1);

                if ($count <= 0) {
                    throw new Exception("Updating tbllogicnumbers failed");
                }

                $connection->commit();
                $this->closeConnection();
                return ["status" => "Bill Cancelled"];
            } catch (Exception $e) {
                $connection->rollBack();
                return ["status" => $e->getMessage()];
                //return ["status" => $e->getMessage(), "data" => $ledgerData];
            }
        }

        public function addOrUpdateBillWithReading($connection, $obj, $action) {

            $AccountNo = $obj['AccountNumber'];
            $billingMonth = $obj["BillingMonth"];

            $CustomerName = trim($obj['CustomerName']);
            $ServiceAddress = trim($obj['ServiceAddress']);
            $Zone = $obj['Zone'];
            $MeterNo =  trim($obj['MeterNo']);
            $RateSchedule =  trim($obj['RateSchedule']);

            if ($obj['IsSenior'] == true) {
                $IsSenior = "Yes";
            } else {
                $IsSenior = "No";
            }
            $CurrentReading = $obj["CurrentReading"];
            $LastMeterReading = $obj["LastMeterReading"];
            $Consumption = $obj["Consumption"];
            $AverageCons = $obj["AverageCons"];
            $MeterReader = $obj["MeterReader"];

            $Amount = $obj["Amount"];
            $SeniorDiscount = $obj["SeniorDiscount"];
            $DateFromFormatted = $obj["DateFromFormatted"];
            $DateToFormatted = $obj["DateToFormatted"];
            $DueDateFormatted = $obj["DueDateFormatted"];

            $createdBy = $obj["CreatedBy"];


            //curent date
            $currentdate = date("Y-m-d");
            //compute disconnection date
            $disconnectionDate = new DateTime($DueDateFormatted);
            $disconnectionDate->modify('+1 day');
            $newdisconnectionDate = $disconnectionDate->format('Y-m-d');

            if ($action === "Add") {
                //get last billno from tbllogic numbers
                $billno = intval($this->getLogicNumber("BillNo")[0]["number"]);

                //insert bill to database
                $sql = "INSERT INTO Bills (BillNo, AccountNumber, CustomerName, CustomerAddress, DateFrom, 
                ReadingDate, DueDate, DiscDate, PreviousReading, Reading, 
                BillingMonth, BillStatus, RateSchedule, isSenior, Consumption, AverageCons, 
                AmountDue, isPaid, MeterReader, SeniorDiscount, MeterNo, 
                isCollectionCreated, createdBy, dateCreated, Zone) OUTPUT Inserted.* 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $connection->prepare($sql);
                $stmt->execute([
                    $billno, $AccountNo, $CustomerName, $ServiceAddress, $DateFromFormatted, 
                    $DateToFormatted, $DueDateFormatted, $newdisconnectionDate, $LastMeterReading, $CurrentReading, 
                    $billingMonth, 'Pending', $RateSchedule, $IsSenior, $Consumption, $AverageCons, 
                    $Amount, 'No', $MeterReader, $SeniorDiscount, $MeterNo, 
                    'No', $createdBy, $currentdate, $Zone
                ]);
            } else if ($action === "Update") {
                $billNumber = $obj['BillNo'];

                $sql = "UPDATE Bills SET ReadingDate = ?, DueDate = ?, DiscDate = ?, PreviousReading = ?, Reading = ?, 
                    RateSchedule = ?, isSenior = ?, Consumption = ?, AverageCons = ?, 
                    AmountDue = ?, MeterReader = ?, SeniorDiscount = ?, MeterNo = ?, 
                    createdBy = ?, dateCreated = ?, Zone = ? WHERE BillNo = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([ 
                    $DateToFormatted, $DueDateFormatted, $newdisconnectionDate, $LastMeterReading, $CurrentReading, 
                    $RateSchedule, $IsSenior, $Consumption, $AverageCons, 
                    $Amount, $MeterReader, $SeniorDiscount, $MeterNo, 
                    $createdBy, $currentdate, $Zone, $billNumber
                ]);
            }

             

            $result = $stmt->fetchAll();

            if ($stmt->rowCount() <= 0 || !$stmt) {
                return ["status" => 0, "result" => []];
            } else {
                return ["status" => 1, "result" => $obj];
            }
        }

        public function addBillsWithEmptyReading($connection, $bill, $billno) {

            $AccountNo =  $bill['AccountNumber'];
            $CustomerName =  trim($bill['CustomerName']);
            $CustomerAddress =  trim($bill['CustomerAddress']);
            $DateFrom = date("Y-m-d", strtotime($bill['DateFrom']));
            $LastMeterReading = intval($bill['LastMeterReading']);
            $CurrentReading = intval($bill['Reading']);
            $Consumption = intval($bill['Consumption']);
            $BillingMonth =  $bill['BillingMonth'];
            $BillStatus =  $bill['BillStatus'];
            $RateSchedule =  $bill['RateSchedule'];
            $Zone =  $bill['Zone'];
            if ($bill['IsSenior'] == true) {
                $IsSenior = "Yes";
            } else {
                $IsSenior = "No";
            }
            $AmountDue = intval($bill['AmountDue']);
            $IsPaid =  $bill['IsPaid'];
            $MeterReader =  $bill['MeterReader'];
            $AverageCons = intval($bill['AverageCons']);
            $SeniorDiscount = intval($bill['SeniorDiscount']);
            $MeterNo =  $bill['MeterNo'];
            $createdBy = $bill['createdBy'];
            $dateCreated = date("Y-m-d", strtotime($bill['dateCreated']));

            //insert bill to database
            $sql = "INSERT INTO Bills (BillNo, AccountNumber, CustomerName, CustomerAddress, 
            PreviousReading, Reading, Consumption, BillingMonth, BillStatus, RateSchedule, [Zone], DateFrom, IsSenior, 
            AmountDue, IsPaid, MeterReader, AverageCons, SeniorDiscount, 
            MeterNo, createdBy, dateCreated) OUTPUT Inserted.* 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $billno, $AccountNo, $CustomerName, $CustomerAddress, 
                $LastMeterReading, $CurrentReading, $Consumption, $BillingMonth, 
                $BillStatus, $RateSchedule, $Zone, $DateFrom, $IsSenior, $AmountDue, $IsPaid, 
                $MeterReader, $AverageCons, $SeniorDiscount, $MeterNo, $createdBy, $dateCreated
            ]);

            $result = $stmt->fetchAll();

            if ($stmt->rowCount() <= 0) {
                return ["status" => 0, "result" => [], "message" => $stmt->errorInfo()[2]];
            } else {
                return ["status" => 1, "result" => $result];
            }
        
        }

        // public function addBillChargesWithEmptyReading($connection, $billInfo, $billCharges) {

        //     $obj = json_decode($billInfo, true);
        //     $billno = $this->getLogicNumber("BillNo")[0]["number"];
        //     $AccountNo =  trim($obj['AccountNo']);
        //     $billingMonth = $obj["BillingMonth"];
        //     $CustomerName =  trim($obj['CustomerName']);
        //     $Zone = $obj['Zone'];
        //     $RateSchedule =  trim($obj['RateSchedule']);
        //     $MeterReader = $obj["MeterReader"];

        //     foreach ($billCharges as $billcharge) {
        //         $sql = "INSERT INTO BillCharges (BillNumber, AccountNumber, [AccountName], [BillingMonth], 
        //             [Zone], [ChargeType], [ChargeRate], [ChargeID], [Category], [Entry], 
        //             [Particulars],[Amount],[IsPaid]
        //             ,[Cancelled],[Status]
        //             ,[isCollectionCreated],[Reader],[RateSchedule], [ScheduleChargesID]) 
        //             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        //         $stmt = $connection->prepare($sql);
        //         $stmt->execute([
        //             $billno, $AccountNo, $CustomerName, $billingMonth, $Zone, 
        //             $billcharge["ChargeType"], $billcharge["ChargeRate"], $billcharge["ChargeID"], $billcharge["Category"], $billcharge["Entry"], 
        //             $billcharge["Particular"], $billcharge["Amount"], 'No', 'No', 'Pending', 
        //             'No', $MeterReader, $RateSchedule, $billcharge["ScheduleChargesID"]
        //         ]);
        //         if ($stmt->rowCount() <= 0) {
        //             return ["status" => 0];
        //         } else {
        //             return ["status" => 1];
        //         }
        //     }
            
        // }

        public function addOrUpdateBillCharges($connection, $obj, $billCharge, $action) {
            $AccountNo =  trim($obj['AccountNumber']);
            $billingMonth = $obj["BillingMonth"];
            $CustomerName =  trim($obj['CustomerName']);
            $Zone = $obj['Zone'];
            $RateSchedule =  trim($obj['RateSchedule']);
            $MeterReader = $obj["MeterReader"];

            if ($action === "Add") {
                $billno = intval($this->getLogicNumber("BillNo")[0]["number"]);

                $sql = "INSERT INTO BillCharges (BillNumber, AccountNumber, [AccountName], [BillingMonth], 
                [Zone], [ChargeType], [ChargeRate], [ChargeID], [Category], [Entry], 
                [Particulars],[Amount],[IsPaid]
                ,[Cancelled],[Status]
                ,[isCollectionCreated],[Reader],[RateSchedule], [ScheduleChargesID]) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $connection->prepare($sql);
                $stmt->execute([
                    $billno, $AccountNo, $CustomerName, $billingMonth, $Zone, 
                    $billCharge["ChargeType"], $billCharge["ChargeRate"], $billCharge["ChargeID"], $billCharge["Category"], $billCharge["Entry"], 
                    $billCharge["Particular"], $billCharge["Amount"], 'No', 'No', 'Pending', 
                    'No', $MeterReader, $RateSchedule, $billCharge["ScheduleChargesID"]
                ]);

            } elseif ($action === "Update") {
                $billChargesID = $billCharge["BillChargesID"];

                $sql = "UPDATE BillCharges SET [Amount] = ? WHERE BillChargesID = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$billCharge["Amount"], $billChargesID]);
                //throw new Exception($billChargesID);
                
            } else {
                throw new Exception("Error");
            }
            
            if (!$stmt) {
                return ["status" => 0];
            } else {
                return ["status" => 1, "message" => $billCharge];
            }
            
        }

        public function addToBillsTemp($connection, $billingMonth, $zones) {
            $newZones = "'" . implode("','", $zones) . "'";
            // Enable identity insert
            //$connection->exec("SET IDENTITY_INSERT $table_name ON");

            // $sql = "INSERT into Billstemp (
            //     billid,BillNo,AccountNumber,CustomerName,CustomerAddress,[Zone],DateFrom,ReadingDate,DueDate,DiscDate 
            //    ,PreviousReading,Reading,BillingMonth,BillStatus,RateSchedule,MeterSize,isSenior,Consumption, AverageCons, AmountDue
            //    ,IsPaid,MeterReader,SeniorDiscount,MeterNo 
            //    ,CRNo,IsCollectionCreated,createdBy,postedBy,dateCreated,datePaid) 
            //    SELECT 
            //    billid,BillNo,AccountNumber,CustomerName,CustomerAddress,[Zone],DateFrom,ReadingDate,DueDate,DiscDate 
            //    ,PreviousReading,Reading,BillingMonth,BillStatus,RateSchedule,MeterSize,isSenior,Consumption, AverageCons, AmountDue
            //    ,IsPaid,MeterReader,SeniorDiscount,MeterNo 
            //    ,CRNo,IsCollectionCreated,createdBy,postedBy,dateCreated,datePaid 
            //    FROM Bills where BillStatus = 'Pending' and [Zone] in ('$newZones') and BillingMonth = '$billingMonth'";

            $sql = "SELECT billid,BillNo,AccountNumber,CustomerName,CustomerAddress,[Zone],DateFrom,ReadingDate,DueDate,DiscDate 
            ,PreviousReading,Reading,BillingMonth,BillStatus,RateSchedule,MeterSize,isSenior,Consumption, AverageCons, AmountDue
            ,IsPaid,MeterReader,SeniorDiscount,MeterNo 
            ,CRNo,IsCollectionCreated,createdBy,postedBy,dateCreated,datePaid 
            FROM Bills where BillStatus = 'Pending' and [Zone] in ($newZones) and BillingMonth = '$billingMonth'";
            // $sql = "SELECT 
            // *
            // FROM Bills";

            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();
            //$connection->exec("SET IDENTITY_INSERT $table_name OFF");


            if ($stmt->rowCount() <= 0 || !$stmt) {
                return ["status" => 0, "result" => $sql, "message" => $stmt->errorInfo()[2]];
            } else {
                return ["status" => 1, "result" => $newZones];
            }
        }


        public function searchBill($billingMonth, $billStatus, $zone) {
            $connection = $this->openConnection();


            if ($billStatus == "All") {
                $sql = "SELECT * FROM Bills WHERE BillingMonth = ? AND [Zone] = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([
                    $billingMonth, $zone
                ]);
            } else {
                $sql = "SELECT * FROM Bills WHERE BillingMonth = ? AND [Zone] = ? AND BillStatus = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([
                    $billingMonth, $zone, $billStatus
                ]);
            }

            $result = $stmt->fetchAll();

            if (!$stmt || $stmt->rowCount() <= 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function addCanceledBillRecord($connection, $billInfo) {
            $obj = json_decode($billInfo, true);
            $referenceNo =  $obj['ReferenceNo'];
            $accountNo =  $obj['AccountNumber'];
            $billNo =  $obj['BillNo'];
            $remarks =  $obj['Remarks'];
            $username =  $obj['Username'];
            $date =  $obj['CurrentDate'];
            $billingMonth =  $obj['BillingMonth'];

            
            $sql = "INSERT INTO BillCancelled ([RefNo],[AccountNo],[BillNo],[Remarks],
                        [CancelledBy],[DateCancelled],[Billcovered]) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $referenceNo, $accountNo, $billNo, $remarks, $username, 
                $date, $billingMonth
            ]);

            if (!$stmt || $stmt->rowCount() <= 0) {
                throw new Exception("Adding canceled bill record failed " . $stmt->errorInfo()[2]);
            }

            //return $connection->lastInsertId();
        }

        public function updateBillStatus($connection, $tableName, $billStatus, $billno, $postedBy=null) {
            if ($postedBy === null) {
                if ($tableName == "Bills") {
                    $sql = "UPDATE Bills SET [BillStatus] = ? WHERE BillNo = ?";
                    $tempArray = [$billStatus, $billno];
                } else if ($tableName == "BillCharges") {
                    $sql = "UPDATE BillCharges SET [Status] = ? WHERE BillNumber = ?";
                    $tempArray = [$billStatus, $billno];
                } else {
                    throw new Exception("Table name is not bills nor billcharges");
                }
            } else {
                if ($tableName == "Bills") {
                    $sql = "UPDATE Bills SET BillStatus = ?, PostedBy = ? WHERE BillNo = ?";
                    $tempArray = [$billStatus, $postedBy, $billno];
                } else if ($tableName == "BillCharges") {
                    $sql = "UPDATE BillCharges SET [Status] = ? WHERE BillNumber = ?";
                    $tempArray = [$billStatus, $billno];
                } else {
                    throw new Exception("Table name is not bills nor billcharges posted by: " . $postedBy);
                }
            }

            $stmt = $connection->prepare($sql);
            $stmt->execute($tempArray); 

            if (!$stmt) {
                return 0;
            } else {
                return 1;
            }
        }
        //Gagawin bukas
        public function getPreviousBillNo($connection, $accNumber) {
            $sql = "SELECT MAX(BillNo) as BillNo from Bills where AccountNumber = ? AND BillStatus != ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$accNumber, 'Cancelled']);
            if (!$stmt) {
                throw new Exception("Error getting previous bill: " . $stmt->errorInfo()[2]);
            }

            $result = $stmt->fetchAll();
            return $result[0]['BillNo'];
        }

        public function updateLastReadingDate($connection, $prevBillNo, $accno) {
            if ($prevBillNo == null) {
                $consumerInstance = new Consumer();
                $consumerInfo = $consumerInstance->fetchConsumerInfoByAccNo($accno);
                $dateInstalled = $consumerInfo["DateInstalled"];

                $sql = "UPDATE Consumers set LasReadingDate = ? WHERE AccountNo = ?";
                $tempArray = [$dateInstalled, $accno];
            } else {
                $sql = "UPDATE Consumers set LasReadingDate = (select ReadingDate from Bills where BillNo = ?) WHERE AccountNo = ?";
                $tempArray = [$prevBillNo, $accno];
            }
            $stmt = $connection->prepare($sql);
            $stmt->execute($tempArray);

            if (!$stmt || $stmt->rowCount() <= 0) {
                throw new Exception("Error updating last reading date: " . $stmt->errorInfo()[2]);
                //throw new Exception("Error updating last reading date: " . $prevBillNo);

            }
        }

        // public function addLedgerEntry($connection, $ledgerData) {
        //     $sql = "INSERT into ConsumerLedger 
        //             (ledgerAccountNo, ledgerDate, ledgerRefNo, ledgerParticulars, ledgerReading, 
        //             ledgerConsumption, ledgerDiscount, ledgerAmount, ledgerBalance) 
        //             values (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        //     $stmt = $connection->prepare($sql);
        //     $stmt->execute([
        //         $ledgerData['ledgerAccountNo'], $ledgerData['ledgerDate'], $ledgerData['ledgerRefNo'], $ledgerData['ledgerParticulars'], $ledgerData['ledgerReading'], 
        //         $ledgerData['ledgerConsumption'], $ledgerData['ledgerDiscount'], $ledgerData['ledgerAmount'], $ledgerData['ledgerBalance']
        //     ]);

        //     if (!$stmt || $stmt->rowCount() <= 0) {
        //         throw new Exception("Error adding ledger entry: " . $stmt->errorInfo()[2]);
        //     }

        // }

        public function checkIfBillExists($connection, $billingMonth, $AccountNo) {
            //check if bill is already created
            $sql = "SELECT * FROM Bills WHERE BillingMonth = '$billingMonth' AND AccountNumber='$AccountNo' AND BillStatus != 'Cancelled'";
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $stmt->fetchAll();

            if (!$stmt || $stmt->rowCount() >= 1) {
                return 0;
                //throw new Exception("Bill for this month already exist");
            } else {
                return 1;
            }
        }

        public function checkIfBillNumberExists($connection, $billno) {
            //check if bill is already created
            $sql = "SELECT * FROM Bills WHERE BillNo = '$billno'";
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $stmt->fetchAll();

            if (!$stmt || $stmt->rowCount() >= 1) {
                throw new Exception("Bill Number already exist");
            }
        }

        public function checkIfPendingBillByAccountNo($connection, $accno) {
            //check if bill is already created
            $sql = "SELECT * FROM Bills WHERE AccountNumber = '$accno' AND BillStatus = 'Pending'";
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $stmt->fetchAll();

            if (!$stmt || $stmt->rowCount() >= 1) {
                throw new Exception("There is a pending bill for this account");
            }
        }

        public function checkIfPendingBillByZone($connection, $zone) {
            //check if bill is already created by zone
            $sql = "SELECT * from Bills where BillStatus = 'Pending' and Zone = ? ";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$zone]);
            $result = $stmt->fetchAll();

            if (!$stmt || $stmt->rowCount() >= 1) {
                return 0;
                //throw new Exception("There is a pending bill for " . $zone);
            } else {
                return 1;
            }
        }

        public function getLogicNumber($remarks) {
            $logicNumberInstance = new LogicNumber();
            $result = $logicNumberInstance->fetchLogicNumber($remarks);
            return $result;
        }

        public function computeDiscounts($SeniorDiscount) {
            return $SeniorDiscount;
        }

        public function computeTotalAmountDue($amountdue, $billdiscount) {
            return $amountdue - $billdiscount;
        }

        public function updateBillChargesStatus($bill_no, $connection=null) {
            if ($connection == null) {
                $connection = $this->openConnection();
            }

            $sql = "UPDATE BillCharges SET [Status] = 'Posted' WHERE BillNumber = $bill_no";
            $stmt = $connection->prepare($sql);
            $stmt->execute();

            if (!$stmt) {
                return ["status" => 0];
            } else {
                return ["status" => 1];
            }
        }

        public function printBill($receipt) {
            $obj = json_decode($receipt, true);
            $billInfo = $obj["billInfo"];

            $amountDue = $billInfo["AmountDue"];
            $totalAmountDue = $amountDue - $billInfo["SeniorDiscount"];
            $surcharge = $amountDue * 0.15;
            $amountAfterDue = $totalAmountDue + $surcharge;

            try {
            
                $connector = new WindowsPrintConnector("smb://192.168.10.37/resibo");
                //$connector = new DummyPrintConnector();
            
                $printer = new Printer($connector);
                // Convert Uint8Array data to a string
                $printer->initialize();
            
                $printer->feed();
                        
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setEmphasis(true);
            
                $printer->text("Republic of the Philippines\n");
                $printer->text($obj['companyName']."\n");
            
                $printer->setEmphasis(false);
            
                $printer->text($obj['companyAddress1']."\n");
                $printer->text($obj['companyAddress2']."\n");
            
                $printer->feed();
            
                $printer->setEmphasis(true);
                $printer->text("WATER BILL\n");
                $printer->setEmphasis(false);
            
                $printer->feed();
            
                $printer->initialize();
                $printer->text("Account Information     Bill NO: ".$billInfo['BillNo']."\n");
                $printer->text("Account No  :   " . $billInfo['AccountNumber'] ."\n");
                $printer->text("Name        :     " . $billInfo['CustomerName'] ."\n");
                $printer->text("Address     :     " . $billInfo['CustomerAddress'] ."\n");
                $printer->text("Class       :     ". $billInfo['RateSchedule']."\n");
                $printer->text("Meter No    :     " .$billInfo['MeterNo'] . "\n");
                $printer->text("Ave. Cons   :     ".$billInfo['AverageCons']."\n");
                $printer->feed();
            
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setEmphasis(true);
                $printer->text("BILLING DETAILS\n");
                $printer->setEmphasis(false);
            
                $printer->feed();
            
                $printer->initialize();
                $printer->text("Previous Reading :   ".$billInfo['PreviousReading']."\n");
                $printer->text("Current Reading  :   ".$billInfo['Reading']."\n");
                $printer->text("Consumption      :   ".$billInfo['Consumption']."\n");
                $printer->feed();
            
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setEmphasis(true);
                $printer->text("PERIOD COVERED\n");
                $printer->setEmphasis(false);
            
                $printer->feed();
            
                $printer->initialize();
                $printer->text("FROM                |       TO\n");
                $printer->text($billInfo['DateFrom']."          |   ".$billInfo['ReadingDate']."\n");
                $printer->feed();
            
                $printer->text("Due Date            |   Disconnection Date\n");
                $printer->text($billInfo['DueDate']."          |   ".$billInfo['DiscDate']."\n");
            
                $printer->feed();
            
                $printer->text("---------------------------------------");
                $printer->feed();
                $printer->feed();
                $printer->text("BILLING SUMMARY     |       AMOUNT\n");
                $printer->feed();
                $printer->text("Current Billing     |       ".$billInfo['AmountDue']."\n");
                $printer->text("Senior Citizen Disc |       ".$billInfo['SeniorDiscount']."\n");
                $printer->feed();
                $printer->text("Total Amount Due    |       ".$totalAmountDue."\n");
                $printer->text("Surcharge(11/30/23) |       ".$surcharge."\n");
                $printer->feed();
                $printer->text("---------------------------------------");
                $printer->feed();
                $printer->text("Amount After Due    |       ".$amountAfterDue."\n");
                $printer->text("---------------------------------------");
                $printer->feed();
                $printer->feed();
            
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setEmphasis(true);
                $printer->text("OCTOBER 2023\n");
                $printer->text("PLEASE PAY BEFORE DUE DATE\n");
                $printer->setEmphasis(false);
                $printer->text("Please visit our website:\n");
                $printer->text("pantabanganwater.gov.ph\n");
                $printer->feed();
            
                $printer->qrCode($billInfo['AccountNumber'], Printer::QR_ECLEVEL_L, 10);
            
                $printer->feed();
                $printer->feed();
            
                $printer->cut();
            
                $printer -> close();

                return ["status" => "success"];
        
            } catch (\Exception $e) {
                return ["status" => "Print failed: " . $e->getMessage()];

            }

        }

        public function fetchUnpaidBills($data, $connection=null) {

            if ($connection == null) {
                $connection = $this->openConnection();
            }
            
            $sql = "SELECT * from Bills where AccountNumber = ? AND isPaid = ? AND 
            BillStatus = ? AND NOT isCollectionCreated = ? order by BillNo desc";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $data['AccountNumber'], $data['IsPaid'], 
                $data['BillStatus'], $data['IsCollectionCreated']
            ]);

            if (!$stmt) {
                throw new Exception("There is an error on fetching unpaid bills");
            }

            $result = $stmt->fetchAll();

            $this->closeConnection();
            return $result;

        }

        public function fetchUnpaidBillCharges($data, $connection=null) {

            if ($connection == null) {
                $connection = $this->openConnection();
            }

            $sql = "SELECT * from BillCharges where AccountNumber = ? AND isPaid = ? AND 
            BillStatus = ? AND NOT IsCollectionCreated = ? order by BillNo desc";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $data['AccountNumber'], $data['IsPaid'], 
                $data['BillStatus'], $data['IsCollectionCreated']
            ]);

            if (!$stmt) {
                throw new Exception("There is an error on fetching unpaid bill charges");
            }

            $result = $stmt->fetchAll();

            return $result;
        }

        public function isBillPaid($connection, $billNumber) {
            $sql = "SELECT * FROM Bills WHERE [isCollectionCreated] = 'Yes' AND [BillNo]= ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$billNumber]);

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count >= 1) {
                throw new Exception("Bill Number " .$billNumber. " is already paid");
            }
        }

        public function calculateEarlyPaymentDiscount($amountDue) {
            $discountInstance = new Discount();

            $discount = $discountInstance->fetchDiscount("Early Payment Discount");
            $rate = $discount[0]["DiscountPercent"];

            $earlyPayment = $amountDue * $rate;
            return round($earlyPayment, 2);
        }

        public function updateCrNoIsCollectionCreatedEarlyPayment($connection, $billData) {
            $sql = "UPDATE Bills set IsCollectionCreated = ?, CRNo = ?, earlyPaymentDiscount= ? WHERE BillNo = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $billData["isCollectionCreated"], $billData["orNumber"], $billData["earlyPaymentDiscount"], $billData["billNo"]
            ]);

            if ($stmt->rowCount() <= 0) {
                throw new Exception("Updating Bills Table failed" .$stmt->errorInfo()[2]);
            }
        }

        public function updateCrNoIsCollectionCreated($data, $connection=null) {

            if ($connection == null) {
                $connection = $this->openConnection();
            }

            $sql = "UPDATE Bills set isPaid = ?, isCollectionCreated = ?, CRNo = ? WHERE CRNo = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $data["IsPaid"], $data["IsCollectionCreated"],
                $data["CRNo"], $data["orgCRNo"]
            ]);

            if (!$stmt) {
                return ["status" => 0];
            } else {
                return ["status" => 1];
            }
        }

        public function tagBillAsPaid($isPaid, $datePaid, $CRNo, $connection) {
            $sql = "Update Bills set isPaid = ?, DatePaid = ? WHERE CRNo= ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $isPaid, $datePaid, $CRNo
            ]);

            if (!$stmt) {
                return ["status" => 0];
            } else {
                return ["status" => 1];
            }
        }

        public function tagBillChargesAsPaid($isPaid, $datePaid, $CRNo, $connection) {
            $sql = "Update BillCharges set isPaid = ?, DatePaid = ? WHERE CRNo= ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $isPaid, $datePaid, $CRNo
            ]);

            if (!$stmt) {
                return ["status" => 0];
            } else {
                return ["status" => 1];
            }
        }

        public function fetchBillAdjustmentByAccNo($accountNumber, $connection=null) {
            if ($connection == null) {
                $connection = $this->openConnection();
            }

            $sql = "SELECT * FROM BillAdjustment WHERE AccountNo = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$accountNumber]);

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if (!$stmt) {
                return [];
            } else {
                return $result;
            }
        }

        public function fetchBillAdjustmentByBillNo($billNumber, $connection=null) {
            if ($connection == null) {
                $connection = $this->openConnection();
            }

            $sql = "SELECT * FROM BillAdjustment WHERE BillNo = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$billNumber]);

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if (!$stmt) {
                return [];
            } else {
                return $result;
            }
        }

        public function fetchBillAdjustmentByRefNo($refNo, $connection=null) {
            if ($connection == null) {
                $connection = $this->openConnection();
            }

            $sql = "SELECT * FROM BillAdjustment WHERE RefNo = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$refNo]);

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if (!$stmt) {
                return [];
            } else {
                return $result;
            }
        }

        public function createBillAdjustment($billAdjustmentDetails, $connection=null) {
            $obj = json_decode($billAdjustmentDetails, true);

            if ($connection == null) {
                $connection = $this->openConnection();
            }

            try {
                $connection->beginTransaction();

                $billInfo = $obj["BillInfo"];

                //validate if refnumber already exist in billadjustment table
                $count = $this->validateRefNo("BillAdjustment", "RefNo", $obj["RefNo"], $connection);
                if ($count > 1) {
                    throw new Exception("Reference Number Already Exists");
                }

                $sql = "INSERT INTO BIllAdjustment ([RefNo],[AccountNo],[AccountName],[Remarks],[BillNo],[Date],[Status]
                ,[CreatedBy],[ApprovedBy],[BillCovered],[OldAmountDue],[OldDiscount], [OldAdvance],[NewAmountDue],[NewDiscount]
                ,[NewAdvance],[Category]) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $connection->prepare($sql);
                $stmt->execute([
                    $obj["RefNo"], $billInfo["AccountNumber"], $billInfo["CustomerName"], $obj["Remarks"], 
                    $billInfo["BillNo"], $obj["NewDate"], $obj["Status"], $obj["ApprovedBy"], $obj["Status"],
                    $billInfo["BillingMonth"], $obj["OldAmountDue"], $obj["OldDiscount"], $obj["OldAdvance"], $obj["NewAmountDue"],
                    $obj["NewDiscount"], $obj["NewAdvance"], $obj["Category"],
                ]);

                if ($stmt->rowCount() <= 0) {
                    throw new Exception("inserting to collection charges failed " .$stmt->errorInfo()[2]);
                }


                //update logic number
                $logicNumberInstance = new LogicNumber();
                $count = $logicNumberInstance->updateLogicNumber("Bill Adjustment", 1);
                if ($count <= 0) {
                    throw new Exception("Updating tbllogicnumbers failed");
                }

                $connection->commit();
                $this->closeConnection();
                return ['status' => 'Bill Adjusted'];
            } catch (Exception $e) {
                $connection->rollBack();
                return ["status" => $e->getMessage()];
            }
            
        }

        public function editBillAdjustment($billAdjustmentDetails, $connection=null) {
            $obj = json_decode($billAdjustmentDetails, true);

            if ($connection == null) {
                $connection = $this->openConnection();
            }

            try {
                $connection->beginTransaction();
                //update bill adjustment details 
                $sql = "UPDATE BIllAdjustment Set NewAmountDue = ?, NewDiscount = ?, NewAdvance = ?, Remarks = ? WHERE RefNo = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$obj["NewAmountDue"], $obj["NewDiscount"], $obj["NewAdvance"], $obj["Remarks"], $obj["RefNo"]]);

                if (!$stmt) {
                    throw new Exception("Updating Bill Adjustment details failed");
                }

                $connection->commit();
                $this->closeConnection();
                return ['status' => 'Bill Adjustment Edited'];
            } catch (Exception $e) {
                $connection->rollBack();
                return ["status" => $e->getMessage()];
            }
        }

        public function cancelBillAdjustment($billAdjustmentDetails, $connection=null) {
            $obj = json_decode($billAdjustmentDetails, true);
            $billInfo = $obj['BillInfo'];

            if ($connection == null) {
                $connection = $this->openConnection();
            }

            try {
                $connection->beginTransaction();

                //update bill adjustment status to cancelled
                $sql = "UPDATE BIllAdjustment Set [Status] = ? WHERE RefNo = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$obj["NewStatus"], $obj["RefNo"]]);

                if (!$stmt) {
                    throw new Exception("Updating Bill Adjustment status failed");
                }

                if ($obj['Status'] == "Posted") {
                    //fetch unpaid bills
                    $billTotal = 0;
                    $billChargesTotal = 0;

                    $data = [
                        "AccountNumber" => $billInfo['AccountNumber'],
                        "IsPaid" => "No",
                        "BillStatus" => "Posted",
                        "IsCollectionCreated" => "Yes",
                    ];
                    
                    $unpaidBills = $this->fetchUnpaidBills($data, $connection);
                    foreach ($unpaidBills as $unpaidBill) {
                        $total =  $unpaidBill["AmountDue"] - ($unpaidBill["AdvancePayment"] + $unpaidBill["SeniorDiscount"]);
                        $billTotal += $total;
                    }

                    //fetch unpaid bill charges
                    $data = [
                        "AccountNumber" => $billInfo['AccountNumber'],
                        "IsPaid" => "No",
                        "BillStatus" => "Posted",
                        "IsCollectionCreated" => "Yes",
                    ];
                    $unpaidBillCharges = $this->fetchUnpaidBillCharges($data, $connection);
                    foreach ($unpaidBillCharges as $unpaidBillCharge) {
                        $total =  $unpaidBillCharge["Amount"];
                        $billChargesTotal += $total;
                    }
                    $totalAmount = ($obj['NewBillTotal'] - $obj['OldBilltotal']) * -1;
                    $totalBalance = $billTotal + $billChargesTotal;

                    //insert ledger data that the bill adjustment is cancelled
                    $ledgerData = [
                        "ledgerAccountNo" => $billInfo['AccountNumber'],
                        "ledgerRefNo" => $obj["RefNo"],
                        "ledgerDate" => $obj["LedgerDate"],
                        "ledgerParticulars" => "Bill Adjustment Cancelled",
                        "ledgerReading" => "",
                        "ledgerConsumption" => "",
                        "ledgerAmount" => $totalAmount,
                        "ledgerDiscount" => "",
                        "ledgerBalance" => $obj['OldBilltotal']
                    ];
                    $consumerLedgerInstance = new ConsumerLedger();
                    $consumerLedger = $consumerLedgerInstance->addLedgerEntry($ledgerData, $connection);

                    if ($consumerLedger['status'] === 0) {
                        throw new Exception("error adding bill adjustment cancelled to consumer's ledger");
                    }

                    //update adjustment on bills table
                    $sql = "UPDATE Bills Set Adjustment = ?, SeniorDiscount = ?, AdvancePayment = ? WHERE AccountNumber = ?";
                    $stmt = $connection->prepare($sql);
                    $stmt->execute([$obj["NewBillAdjustment"], $obj["OldDiscount"], $obj["OldAdvance"], $billInfo["AccountNumber"]]);

                    if (!$stmt) {
                        throw new Exception("Updating Adjustment, SeniorDiscount, AdvancePayment on Bills table failed");
                    }
                }
                $connection->commit();
                $this->closeConnection();
                return ['status' => 'Bill Adjustment Cancelled'];
            } catch (Exception $e) {
                $connection->rollBack();
                return ["status" => $e->getMessage()];
            }
        }

        public function postBillAdjustment($billAdjustmentDetails, $connection=null) {
            $obj = json_decode($billAdjustmentDetails, true);
            $billInfo = $obj['BillInfo'];

            if ($connection == null) {
                $connection = $this->openConnection();
            }

            try {
                $connection->beginTransaction();

                //update bill adjustment status 
                $sql = "UPDATE BIllAdjustment Set [Status] = ?, DatePosted = ?, ApprovedBy = ? WHERE RefNo = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$obj["Status"], $obj["DatePosted"], $obj["ApprovedBy"], $obj["RefNo"]]);

                if (!$stmt) {
                    throw new Exception("Updating Bill Adjustment status failed");
                }

                //fetch unpaid bills
                $data = [
                    "AccountNumber" => $billInfo['AccountNumber'],
                    "IsPaid" => "No",
                    "BillStatus" => "Posted",
                    "IsCollectionCreated" => "Yes",
                ];
                $billTotal = 0;
                $billChargesTotal = 0;

                $unpaidBills = $this->fetchUnpaidBills($data, $connection);
                foreach ($unpaidBills as $unpaidBill) {
                    $total =  $unpaidBill["AmountDue"] - ($unpaidBill["AdvancePayment"] + $unpaidBill["SeniorDiscount"]);
                    $billTotal += $total;
                }

                //fetch unpaid bill charges
                $data = [
                    "AccountNumber" => $billInfo['AccountNumber'],
                    "IsPaid" => "No",
                    "BillStatus" => "Posted",
                    "IsCollectionCreated" => "Yes",
                ];
                $unpaidBillCharges = $this->fetchUnpaidBillCharges($data, $connection);
                foreach ($unpaidBillCharges as $unpaidBillCharge) {
                    $total =  $unpaidBillCharge["Amount"];
                    $billChargesTotal += $total;
                }
                $totalAmount = $obj['NewBillTotal'] - $obj['OldBilltotal'];
                $totalBalance = $billTotal + $billChargesTotal + $totalAmount;
                //insert bill adjustment data to ledger
                $ledgerData = [
                    "ledgerAccountNo" => $billInfo['AccountNumber'],
                    "ledgerRefNo" => $obj["RefNo"],
                    "ledgerDate" => $obj["DatePosted"],
                    "ledgerParticulars" => "Bill Adjustment(".$obj["Remarks"].")",
                    "ledgerReading" => "",
                    "ledgerConsumption" => "",
                    "ledgerAmount" => $totalAmount,
                    "ledgerDiscount" => "",
                    "ledgerBalance" => $totalBalance
                ];
                $consumerLedger = new ConsumerLedger();
                $consumerLedger = $consumerLedger->addLedgerEntry($ledgerData, $connection);
                if ($consumerLedger['status'] === 0) {
                    throw new Exception("error adding bill adjustment to consumer's ledger");
                }

                if ($obj['isSenior'] == true) {
                    $IsSenior = "Yes";
                } else {
                    $IsSenior = "No";
                }

                //update bills status
                $adjustment = $obj["NewAmountDue"] - $obj["OldAmountDue"];
                $sql = "UPDATE Bills Set Adjustment = ?, SeniorDiscount = ?, AdvancePayment = ?, isSenior = ? WHERE BillNo = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$adjustment, $obj["NewDiscount"], $obj["NewAdvance"], $IsSenior, $billInfo["BillNo"]]);

                if (!$stmt) {
                    throw new Exception("Updating Bills Adjustment, Dicount, AdvancePayment failed");
                }

                $connection->commit();
                $this->closeConnection();
                return ['status' => 'Bill Adjustment Posted'];
            } catch (Exception $e) {
                $connection->rollBack();
                return ["status" => $e->getMessage()];
            }
        }


        public function validateRefNo($tableName, $columnName, $number, $connection=null) {
            if ($connection == null) {
                $connection = $this->openConnection();
            }

            $sql = "SELECT * FROM $tableName WHERE $columnName = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$number]);

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            return $count;
        }
        
    }