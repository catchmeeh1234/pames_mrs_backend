<?php
    require_once '../classes/Connect.class.php';

    $dsn = "sqlsrv:Server=192.168.10.17;Database=pames_staging;TrustServerCertificate=true";
    $user = "sa";
    $password = 'p@$$w0rd';
    $options = array(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);

    try {
        $conn = new PDO($dsn, $user, $password, $options);
        
        $sql = "SELECT [AccountNo]
        ,[Lastname]
        ,[Firstname]
        ,[Middlename]
        ,[ServiceAddress]
        ,[ContactNo]
        ,[Averagee]
        ,[ReadingSeqNo]
        ,[CustomerStatus]
        ,[IsSenior]
        ,[Zone]
        ,[RateSchedule]
        ,[DateCreated]
        ,[DateInstalled]
        ,[InstalledBy]
        ,[MeterNo]
        ,[CompanyName]
        ,[CreatedBy]
        ,[LastMeterReading]
        ,[LandMark]
        ,[MeterSize]
        ,[LastBill]
        ,[LasReadingDate]
        ,[AdvancePayment] FROM Consumers";
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll();

        if (!$stmt || $stmt->rowCount() <= 0) {
            echo json_encode(['status' => "failed" . $stmt->errorInfo()[2]]);
            //throw new Exception("error fetchging consumers: ");
        } else {
            //echo json_encode($result);
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
        }
        $conn = null;

    } catch (PDOException $e) {
        echo "error connecting to database: " . $e->getMessage();
    }