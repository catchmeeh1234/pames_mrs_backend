<?php
    class Bill extends Connect {
        public function selectBills() {
            $connection = $this->openConnection();

            $sql = "SELECT BILLID, BillNo,AccountNumber,CustomerName,CustomerAddress,ReadingDate,DueDate,PreviousReading,Reading,Consumption,Bills.RateSchedule,Bills.MeterSize,
            Bills.Zone, AmountDue,Consumers.MeterNo,ReadingSeqNo,LasReadingDate,Consumers.IsSenior,BillingMonth,IsPaid,BillStatus,MeterReader,Bills.AverageCons
            FROM Bills, Consumers WHERE MeterReader = 'pedro' AND Bills.IsPaid = 'No'AND Bills.BillStatus != 'Cancelled' AND 
            Bills.BillingMonth = 'October 2023' AND Bills.BillStatus = 'Pending' AND Bills.Reading = 0 AND Consumers.AccountNo = Bills.AccountNumber";

            $stmt = $connection->prepare($sql);
            $stmt->execute();

            $result = $stmt->fetchAll();
            if (!$stmt || $stmt->rowCount()) {
                return [];
            } else {
                return $result;
            }
        }
    }