<?php
    require_once 'Bill.class.php';
    require_once 'Charges.class.php';
    require_once 'ConsumerLedger.class.php';
    require_once 'LogicNumber.class.php';

    class OfficialReceipt extends Connect {

        private $authInstance;

        public function __construct() {
            $this->authInstance = new Auth();
        }

        public function createOR($orDetails) {
             //validate JWT
             $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
            $connection = $this->openConnection();

            //get current time
            $currentDateTime = new DateTime();
            $formattedDate = $currentDateTime->format("Y-m-d H:i:s") . ".000";

            //decode json object from the post request
            $obj = json_decode($orDetails, true);
            $accountNumber = $obj['accountNumber'];
            $orNumber = $obj['orNumber'];
            $bills = $obj['billingMonth'];

            try {
                $connection->beginTransaction();

                $billInstance = new Bill();
                $chargesInstance = new Charges();

                //Validate OR number
                $this->validateORNumber($orNumber, $connection);

                //check if the selected bill is already paid
                foreach ($bills as $bill) {
                    if ($bill["Checked"] == true) {
                        $billInstance->isBillPaid($bill["billNumber"], $connection);
                    }
                }
 
                //insert collection details
                $orData = [
                    "orNumber" => $orNumber,
                    "AccountNo" => $accountNumber,
                    "AccountName" => $bills[0]["billDetails"]["CustomerName"],
                    "Address" => $bills[0]["billDetails"]["CustomerAddress"],
                    "CheckNo" => $obj["referenceNumber"],
                    "CheckDate" => $obj["checkDate"],
                    "TotalAmountDue" => $obj["totalAmountDue"],
                    "AdvancePayment" => 0.00,
                    "PaymentDate" => $formattedDate,
                    "Collector" => $obj["username"],
                    "Office" => "Main Office",
                    "CollectionStatus" => "Pending",
                    "PaymentType" => $obj["modeOfPayment"],
                ];
                $this->addCollectionDetails($orData, $connection);



                //insert collection billing
                $isFirstEntry = true;
                foreach ($bills as $bill) {
                    $billDetails = $bill["billDetails"];

                    if ($isFirstEntry == true) {

                        if ($obj["earlyPaymentDiscount"] == true) {
                            $earlyPaymentTotal = $billInstance->calculateEarlyPaymentDiscount($billDetails['AmountDue']);
                        } else {
                            $earlyPaymentTotal = 0.00;
                        }
                        $billType = "BillCurrent";
                    } else {
                        $billType = "Bill";
                        $earlyPaymentTotal = 0.00;
                    }

                      
                    //insert only on  all selected bills
                    if ($bill["Checked"] == true) {
                        //hard coded advance payment to 0.00
                        $orData = [
                            "orNumber" => $orNumber,
                            "AccountNo" => $accountNumber,
                            "AccountName" => $billDetails["CustomerName"],
                            "Address" => $billDetails["CustomerAddress"],
                            "Zone" => $billDetails["Zone"],
                            "BillingDate" => $billDetails["BillingMonth"],
                            "PaymentDate" => $formattedDate,
                            "BillType" => $billType,
                            "BillNo" => $billDetails["BillNo"],
                            "AmountDue" => $billDetails["AmountDue"],
                            "SeniorDiscount" => $billDetails["SeniorDiscount"],
                            "earlyPaymentDiscount" => $earlyPaymentTotal,
                            "AdvancePayment" => 0.00,
                            "CollectionBillingStatus" => "Pending",
                            "Adjustment" => $billDetails["Adjustment"],

                        ];
                        $this->addCollectionBilling($connection, $orData);

                        //fetch reconnection fee from the charges table
                        $reconnectionFee = $chargesInstance->fetchChargeInfo("Reconnection Fee");
                        
                        $orCharges = [
                            "orNumber" => $orNumber,
                            "billNo" => $bill["billNumber"], 
                            "particulars" => $reconnectionFee[0]["Particular"], 
                            "amount" => $reconnectionFee[0]["Amount"], 
                            "chargeID" => $reconnectionFee[0]["ChargeID"],
                            "chargeType" => $reconnectionFee[0]["ChargeType"],
                            "chargeRate" => $reconnectionFee[0]["ComputeRate"], 
                            "category" => $reconnectionFee[0]["Category"], 
                            "entry" => $reconnectionFee[0]["Entry"], 
                            "collectionChargesStatus" => "Pending"
                        ];

                        //add reconnection fee on the first bill only
                        //add reconnection fee is reconection fee is set to true
                        if ($isFirstEntry == true && $obj["reconnectionFee"] == true) {
                            //throw new Exception("TEST1" . json_encode($orCharges));
                            $this->addCollectionCharges($connection, $orCharges);
                        }

                        //Update Bills table
                        $billData = [
                            "isCollectionCreated" => "Yes",
                            "orNumber" => $orNumber,
                            "earlyPaymentDiscount" => $earlyPaymentTotal,
                            "billNo" => $bill["billNumber"],
                        ];                        
                        $billInstance->updateCrNoIsCollectionCreatedEarlyPayment($billData, $connection);

                        //Add Update BillCharges if necessary

                        $isFirstEntry = false;
                    }
            
                }   
                
                //update OR number
                $logicNumberInstance = new LogicNumber();
                $logicNumberInstance->updateLogicNumber("CR Number", 1);

                $connection->commit();
                $this->closeConnection();
                return ['status' => 'OR Created'];

            } catch (Exception $e) {
                $connection->rollBack();
                return ["status" => $e->getMessage()];
            }
        }  
        
        public function validateORNumber($orNumber, $connection=null) {
            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

                $connection = $this->openConnection();
            }

            $sql = "SELECT * FROM Collection_Details WHERE CRNo = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$orNumber]);

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count > 0) {
                throw new Exception("OR number already exists");
            }
        }

        

        public function addCollectionDetails($orData, $connection=null) {
            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

                $connection = $this->openConnection();
            }

            //advance payment is hard coded to 0.00
            $sql = "INSERT INTO Collection_Details (
                CRNo, AccountNo, AccountName, [Address], CheckNo, CheckDate, 
                TotalAmountDue, AdvancePayment, PaymentDate, Collector,Office, 
                CollectionStatus, PaymentType)
                Values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $orData['orNumber'], $orData['AccountNo'], $orData['AccountName'], $orData['Address'], $orData['CheckNo'], $orData['CheckDate'], 
                $orData['TotalAmountDue'], $orData["AdvancePayment"], $orData["PaymentDate"], $orData["Collector"], $orData["Office"],
                $orData["CollectionStatus"], $orData['PaymentType']
            ]);

            $result = $stmt->fetchAll();
            if ($stmt->rowCount() <= 0) {
                throw new Exception("inserting to collection details failed " .$stmt->errorInfo()[2]);
            }
        }

        public function addCollectionBilling($orData, $connection=null) {
            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

                $connection = $this->openConnection();
            }

            $sql = "INSERT INTO CollectionBilling (
                CRNo, AccountNo, AccountName, [Address], [Zone], BillingDate, 
                PaymentDate, BillType, BillNo, AmountDue, Discount, earlyPaymentDiscount,  
                AdvancePayment, CollectionBillingStatus, Adjustment)
                Values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $orData['orNumber'], $orData['AccountNo'], $orData['AccountName'], $orData['Address'], $orData['Zone'], $orData['BillingDate'], 
                $orData['PaymentDate'], $orData['BillType'], $orData['BillNo'], $orData['AmountDue'], $orData["SeniorDiscount"], $orData["earlyPaymentDiscount"],
                $orData["AdvancePayment"], $orData['CollectionBillingStatus'], $orData['Adjustment']
            ]);

            $result = $stmt->fetchAll();
            if ($stmt->rowCount() <= 0) {
                throw new Exception("inserting to collection billing failed " .$stmt->errorInfo()[2]);
            }

        }

        public function addCollectionCharges($orData, $connection=null) {
            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

                $connection = $this->openConnection();
            }

            $sql = "INSERT INTO CollectionCharges (
                CRNo, BillNo, Particulars, Amount, ChargeID, ChargeType, ChargeRate,
                Category, Entry, CollectionChargesStatus) 
                Values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $orData['orNumber'], $orData['billNo'], $orData['particulars'], $orData['amount'], 
                $orData['chargeID'],  $orData['chargeType'], $orData['chargeRate'], $orData['category'], $orData['entry'], $orData['collectionChargesStatus']
            ]);

            $result = $stmt->fetchAll();
            if ($stmt->rowCount() <= 0) {
                throw new Exception("inserting to collection charges failed " .$stmt->errorInfo()[2]);
            }
        }

        public function fetchORDetailsByAccountNo($accountNumber) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $connection = $this->openConnection();
            $sql = "SELECT * FROM Collection_Details WHERE AccountNo = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$accountNumber]);

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count <= 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function fetchORBillingByORNo($orNumber, $connection=null) {

            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
                $connection = $this->openConnection();
            }

            $sql = "SELECT * FROM CollectionBilling WHERE CRNo = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$orNumber]);

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count <= 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function fetchORChargesByORNo($orNumber, $connection=null) {

            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
                $connection = $this->openConnection();
            }

            $sql = "SELECT * FROM CollectionCharges WHERE CRNo = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$orNumber]);

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count <= 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function fetchLastPaidORByAccountNo($accountNumber) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $connection = $this->openConnection();
            $sql = "SELECT * FROM Collection_Details WHERE AccountNo = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$accountNumber]);

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count <= 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function fetchPendingOR() {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $connection = $this->openConnection();
            $sql = "SELECT * FROM Collection_Details WHERE CollectionStatus = 'Pending'";
            $stmt = $connection->prepare($sql);
            $stmt->execute();

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count <= 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function postOR($orDetails) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $connection = $this->openConnection();

            try {
                $obj = json_decode($orDetails, true);

                $connection->beginTransaction();
                
                //update collection details status
                $response = $this->updateCollectionDetailsStatus('Posted', $obj['CollectionID'], $connection);
                if ($response["status"] === 0) {
                    throw new Exception("error updating collection details status");
                }

                //update collection billing status
                $response = $this->updateCollectionBillingStatus('Posted', $obj['CRNo'], $connection);
                if ($response["status"] === 0) {
                    throw new Exception("error updating collection billing status");
                }

                //update collection charges status
                $response = $this->updateCollectionChargesStatus('Posted', $obj['CRNo'], $connection);
                if ($response["status"] === 0) {
                    throw new Exception("error updating collection charges status");
                }

                // update bills
                $billInstance = new Bill();
                $bills = $billInstance->tagBillAsPaid('Yes', $obj['PaymentDate'], $obj['CRNo'], $connection);
                if ($bills["status"] === 0) {
                    throw new Exception("error tagging bill as paid");
                }

                //updating bill charges
                $bills = $billInstance->tagBillChargesAsPaid('Yes', $obj['PaymentDate'], $obj['CRNo'], $connection);
                if ($bills["status"] === 0) {
                    throw new Exception("error tagging bill charges as paid");
                }

                $ledgerBalance = 0;
                //fetch all unpaid collection billing
                $collectionBillings = $this->fetchORBillingByORNo($obj['CRNo'], $connection);
                foreach ($collectionBillings as $collectionBilling) {
                    $total =  ($collectionBilling["AmountDue"] + $collectionBilling["Adjustment"]) - ($collectionBilling["AdvancePayment"] + $collectionBilling["Discount"]);
                    $ledgerBalance += $total;
                }

                //add collection charges to ledger entry
                $collectionCharges = $this->fetchORChargesByORNo($obj['CRNo'], $connection);

                $consumerLedgerInstance = new ConsumerLedger();
                $datetime = new DateTime($obj['PaymentDate']);
                $formattedLedgerDate = $datetime->format('Y-m-d');

                foreach ($collectionCharges as $collectionCharge) {         
                    $ledgerBalance += $collectionCharge['Amount'];

                    $ledgerData = [
                        "ledgerAccountNo" => $obj['AccountNo'],
                        "ledgerRefNo" => $collectionCharge['BillNo'],
                        "ledgerDate" => $formattedLedgerDate,
                        "ledgerParticulars" => $collectionCharge['Particulars'],
                        "ledgerReading" => "",
                        "ledgerConsumption" => "",
                        "ledgerAmount" => $collectionCharge['Amount'],
                        "ledgerDiscount" => "",
                        "ledgerBalance" => $ledgerBalance,
                    ];

                    $consumerLedger = $consumerLedgerInstance->addLedgerEntry($ledgerData, $connection);

                    if ($consumerLedger['status'] === 0) {
                        throw new Exception("error adding collection charges to consumer's ledger");
                    }
                }

                //fetch current bill of this crno
                $currentBill = array_filter($collectionBillings, function($collectionBilling) {
                    return $collectionBilling['BIllType'] === 'BillCurrent';
                });

                 //add early payment discount to ledger entry
                 if ($currentBill[0]["earlyPaymentDiscount"] > 0) {
                    $ledgerBalance -= $currentBill[0]["earlyPaymentDiscount"];
                    $ledgerData = [
                        "ledgerAccountNo" => $obj['AccountNo'],
                        "ledgerRefNo" => $currentBill[0]["BillNo"],
                        "ledgerDate" => $formattedLedgerDate,
                        "ledgerParticulars" => "Early Payment Discount",
                        "ledgerReading" => "",
                        "ledgerConsumption" => "",
                        "ledgerAmount" => "",
                        "ledgerDiscount" => $currentBill[0]["earlyPaymentDiscount"],
                        "ledgerBalance" => $ledgerBalance,
                    ];
                    $consumerLedger = $consumerLedgerInstance->addLedgerEntry($ledgerData, $connection);

                    if ($consumerLedger['status'] === 0) {
                        throw new Exception("error adding early payment to consumer's ledger");
                    }
                 }

                
                //fetch unpaid bills
                $data = [
                    "AccountNumber" => $obj['AccountNo'],
                    "IsPaid" => "No",
                    "BillStatus" => "Posted",
                    "IsCollectionCreated" => "Yes",
                ];
                $unpaidBills = $billInstance->fetchUnpaidBills($data, $connection);

                $totalBill = 0;
                // Iterate through each object and sum the 'amount' values
                foreach ($unpaidBills as $unpaidBill) {
                    if (isset($unpaidBill['AmountDue'])) {
                        $totalBill += ($unpaidBill['AmountDue'] + $unpaidBill['Adjustment']) - ($unpaidBill['SeniorDiscount'] + $unpaidBill['earlyPaymentDiscount']);
                    }
                }

                //fetch unpaid BillCharges
                $data = [
                    "AccountNumber" => $obj['AccountNo'],
                    "IsPaid" => "No",
                    "BillStatus" => "Posted",
                    "IsCollectionCreated" => "Yes",
                ];
                $unpaidBillCharges = $billInstance->fetchUnpaidBillCharges($data, $connection);
                $totalBillCharges = 0;
                // Iterate through each object and sum the 'amount' values
                foreach ($unpaidBillCharges as $unpaidBillCharge) {
                    if (isset($unpaidBillCharge['Amount'])) {
                        $totalBillCharges += $unpaidBillCharge['Amount'];
                    }
                }

                //compute ledger balance for collection
                $ledgerTotal =  ($totalBill + $totalBillCharges) - $obj['AdvancePayment'];

                //add collection payment to ledger entry
                $ledgerData = [
                    "ledgerAccountNo" => $obj['AccountNo'],
                    "ledgerRefNo" => $obj["CRNo"],
                    "ledgerDate" => $formattedLedgerDate,
                    "ledgerParticulars" => "Collection",
                    "ledgerReading" => "",
                    "ledgerConsumption" => "",
                    "ledgerAmount" => "",
                    "ledgerDiscount" => $obj["TotalAmountDue"],
                    "ledgerBalance" => $ledgerTotal,
                ];
                $consumerLedger = $consumerLedgerInstance->addLedgerEntry($ledgerData, $connection);

                if ($consumerLedger['status'] === 0) {
                    throw new Exception("error adding collection to consumer's ledger");
                }

                $connection->commit();
                $this->closeConnection();
                return ['status' => 'OR Posted'];

            } catch (Exception $e) {
                $connection->rollBack();
                return ["status" => $e->getMessage()];
            }
        }

        public function updateCollectionDetailsStatus($status, $collectionID, $connection=null) {
            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

                $connection = $this->openConnection();
            }
            $sql = "UPDATE Collection_Details Set CollectionStatus = ? WHERE CollectionID = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$status, $collectionID]);

            if (!$stmt) {
                return ["status" => 0];
            } else {
                return ["status" => 1];
            }
        }

        public function updateCollectionBillingStatus($status, $crno, $connection=null) {
            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

                $connection = $this->openConnection();
            }
            
            $sql = "Update CollectionBilling Set CollectionBillingStatus = ? WHERE CRNo = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$status, $crno]);

            if (!$stmt) {
                return ["status" => 0];
            } else {
                return ["status" => 1];
            }
        }

        public function updateCollectionChargesStatus($status, $crno, $connection=null) {
            if ($connection == null) {
                //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

                $connection = $this->openConnection();
            }

            $sql = "Update CollectionCharges Set CollectionChargesStatus = ? WHERE CRNo = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$status, $crno]);

            if (!$stmt) {
                return ["status" => 0];
            } else {
                return ["status" => 1];
            }
        }

        public function cancelOR($orDetails) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $obj = json_decode($orDetails, true);
            $collectionStatus = $obj['CollectionStatus'];
            $collectionId = $obj['CollectionID'];
            $CRNo = $obj['CRNo'];

            $connection = $this->openConnection();

            try {
                $connection->beginTransaction();

                $billInstance = new Bill();
                $logicNumberInstance = new LogicNumber();
                $consumerInstance = new Consumer();
                $consumerLedgerInstance = new ConsumerLedger();


                //insert into CollectionCancelled table
                $collectionCancelledData = [
                    "RefNo" => $obj['ReferenceNo'],
                    "AccountNo" => $obj["AccountNo"],
                    "CRNo" => $CRNo,
                    "Remarks" => $obj['Remarks'],
                    "CancelledBy" => $obj['Username'],
                    "DateCancelled" => $obj['CurrentDate']
                ];

                $collectionCancelled = $this->addCollectionCancelled($collectionCancelledData, $connection);

                if ($collectionCancelled['status'] === 0) {
                    throw new Exception("error adding entry to collection cancelled");
                }

                //update collection details collection status to cancelled
                $response = $this->updateCollectionDetailsStatus("Cancelled", $collectionId, $connection);
                if ($response['status'] == 0) {
                    throw new Exception("error updating collection status of collection details");
                }

                //update collection billing status to cancelled
                $response = $this->updateCollectionBillingStatus("Cancelled", $CRNo, $connection);
                if ($response['status'] === 0) {
                    throw new Exception("error updating collection status of collection billing");
                }

                //update collection charges status to cancelled
                $response = $this->updateCollectionChargesStatus("Cancelled", $CRNo, $connection);
                if ($response['status'] === 0) {
                    throw new Exception("error updating collection status of collection charges");
                }

                //update bills IsCollectionCreated to No and CRNo to null base on CRNo
                $collectionCancelledData = [
                    "IsPaid" => 'No',
                    "IsCollectionCreated" => 'No',
                    "CRNo" => null,
                    "orgCRNo" => $obj['CRNo'],
                ];

                $response = $billInstance->updateCrNoIsCollectionCreated($collectionCancelledData, $connection);
                if ($response['status'] === 0) {
                    throw new Exception("error updating crno and is collection created of bills table");
                }

                //$ledgerTotal = $obj['TotalAmountDue'];
                $ledgerTotal = 0;

                if ($collectionStatus == "Posted") {
                    //fetch collection charges
                    $collectionCharges = $this->fetchORChargesByORNo($CRNo, $connection);

                    foreach ($collectionCharges as $collectionCharge) {
                        //add reconnection fee cancelled to ledger
                        $particulars = $collectionCharge['Particulars'];
                        $ledgerData = [
                            "ledgerAccountNo" => $obj['AccountNo'],
                            "ledgerRefNo" => $CRNo,
                            "ledgerDate" => $obj['CurrentDate'],
                            "ledgerParticulars" => "Cancelled($particulars)",
                            "ledgerReading" => "",
                            "ledgerConsumption" => "",
                            "ledgerAmount" => "",
                            "ledgerDiscount" => $collectionCharge['Amount'],
                            "ledgerBalance" => $ledgerTotal += $collectionCharge['Amount']
                        ];

                        $consumerLedger = $consumerLedgerInstance->addLedgerEntry($ledgerData, $connection);

                        if ($consumerLedger['status'] === 0) {
                            throw new Exception("error adding cancelled collection charges to consumer's ledger");
                        }
                    }

                    //fetch collection billing
                    $collectionBillings = $this->fetchORBillingByORNo($CRNo, $connection);

                    foreach ($collectionBillings as $collectionBilling) {
                        //add early payment to cancelled to ledger
                        if ($collectionBilling["earlyPaymentDiscount"] > 0) {
                            $ledgerData = [
                                "ledgerAccountNo" => $obj['AccountNo'],
                                "ledgerRefNo" => $CRNo,
                                "ledgerDate" => $obj['CurrentDate'],
                                "ledgerParticulars" => "Cancelled(Early Payment Discount)",
                                "ledgerReading" => "",
                                "ledgerConsumption" => "",
                                "ledgerAmount" => $collectionBilling['earlyPaymentDiscount'],
                                "ledgerDiscount" => "",
                                "ledgerBalance" => $ledgerTotal -= $collectionBilling['earlyPaymentDiscount']
                            ];

                            $consumerLedger = $consumerLedgerInstance->addLedgerEntry($ledgerData, $connection);

                            if ($consumerLedger['status'] === 0) {
                                throw new Exception("error adding cancelled early payment to consumer's ledger");
                            }
                        }
                        
                    }
                    

                    //compute advance payment
                    $sql = "SELECT (b.AdvancePayment - a.AdvancePayment) as newadvance from Collection_Details a join Customers b on a.AccountNo = b.AccountNo where a.CRNo = ?";
                    $stmt = $connection->prepare($sql);
                    $stmt->execute([$CRNo]);
                    $result = $stmt->fetchAll();

                    if (!$stmt) {
                        throw new Exception("error selecting advance payment of consumers and collections details table");
                    }

                    $data = [
                        "AdvancePayment" => $result[0]['newadvance'],
                        "AccountNo" => $obj["AccountNo"]
                    ];
                    
                    $response = $consumerInstance->updateAdvancePayment($data, $connection);
                    if ($response['status'] === 0) {
                        throw new Exception("error updating advance payment of consumers table");
                    }

                    $connection->commit();

                    $connection->beginTransaction();

                    //fetch unpaid bills
                    $data = [
                        "AccountNumber" => $obj['AccountNo'],
                        "IsPaid" => "No",
                        "BillStatus" => "Posted",
                        "IsCollectionCreated" => "Yes",
                    ];
                    $billTotal = 0;
                    $billChargesTotal = 0;

                    $unpaidBills = $billInstance->fetchUnpaidBills($data, $connection);
                    foreach ($unpaidBills as $unpaidBill) {
                        $total =  ($unpaidBill["AmountDue"] + $unpaidBill["Adjustment"]) - ($unpaidBill["AdvancePayment"] + $unpaidBill["SeniorDiscount"]);
                        $billTotal += $total;
                    }

                    //fetch unpaid bill charges
                    $data = [
                        "AccountNumber" => $obj['AccountNo'],
                        "IsPaid" => "No",
                        "BillStatus" => "Posted",
                        "IsCollectionCreated" => "Yes",
                    ];
                    $unpaidBillCharges = $billInstance->fetchUnpaidBillCharges($data, $connection);
                    foreach ($unpaidBillCharges as $unpaidBillCharge) {
                        $total =  $unpaidBillCharge["Amount"];
                        $billChargesTotal += $total;
                    }

                    //add entry to ledger
                    $ledgerData = [
                        "ledgerAccountNo" => $obj['AccountNo'],
                        "ledgerRefNo" => $obj["ReferenceNo"],
                        "ledgerDate" => $obj['CurrentDate'],
                        "ledgerParticulars" => "Cancelled Collection($CRNo)",
                        "ledgerReading" => "",
                        "ledgerConsumption" => "",
                        "ledgerAmount" => $obj['TotalAmountDue'] - $ledgerTotal,
                        "ledgerDiscount" => "",
                        "ledgerBalance" => $ledgerTotal+=($billTotal + $billChargesTotal) - $result[0]['newadvance']
                    ];

                    $consumerLedger = $consumerLedgerInstance->addLedgerEntry($ledgerData, $connection);

                    if ($consumerLedger['status'] === 0) {
                        throw new Exception("error adding cancelled or to consumer's ledger");
                    }
                    
                }

                //INCREMENT LOGIC NUMBERS COLLECTION CANCELLED
                $logicNumberInstance->updateLogicNumber("CancelCollection", 1);

                $connection->commit();
                $this->closeConnection();
                return ['status' => 'OR Cancelled'];
            } catch (Exception $e) {
                $connection->rollBack();
                return ["status" => $e->getMessage()];
            }   
        }

        public function addCollectionCancelled($collectionCancelledData, $connection=null) {

            if ($connection == null) {
                 //validate JWT
                $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
                
                $connection = $this->openConnection();
            }

            $sql = "INSERT INTO CollectionCancelled ([RefNo],[AccountNo],[CRNo],[Remarks],[CancelledBy],[DateCancelled]) 
            VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $collectionCancelledData["RefNo"], $collectionCancelledData["AccountNo"], $collectionCancelledData["CRNo"], 
                $collectionCancelledData["Remarks"], $collectionCancelledData["CancelledBy"], $collectionCancelledData["DateCancelled"]
            ]);

            if (!$stmt) {
                return ["status" => 0];
            } else {
                return ["status" => 1];
            }
        }

    }
