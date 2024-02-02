<?php
    require_once './classes/Connect.class.php';

    try {
        $json_data = file_get_contents('reponse.json');
        $result = json_decode($json_data, true);    

        $connectInstance = new Connect();
        $connection = $connectInstance->openConnection();

        //add data to PAMES consumers table
        foreach ($result as $row) {
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
                $row['AccountNo'], $row['Lastname'], $row['Firstname'], $row['Middlename'], 
                $row['ServiceAddress'], $row['LandMark'], $row['ContactNo'], $row['MeterNo'],
                $row['ReadingSeqNo'], $row['Zone'], $row['RateSchedule'], $row['DateCreated'], 
                $row['DateInstalled'], $row['CustomerStatus'], $row['IsSenior'],
                $row['InstalledBy'], $row['CreatedBy'], 0, 0
            ]);

            $count = $stmt->rowCount();
            if ($count <= 0 || !$stmt) {
                throw new Exception("Adding account failed: " . $stmt->errorInfo()[2]);
            }

            echo "Accounts Added";
        }

        $connectInstance->closeConnection();
        
        $conn = null;

    } catch (PDOException $e) {
        echo "error connecting to database: " . $e->getMessage();
    }