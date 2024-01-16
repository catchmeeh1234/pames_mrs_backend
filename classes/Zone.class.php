<?php

    class Zone extends Connect {

        public function __construct()
        {
            
        }

        public function fetchZones() {
            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM Zones";
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

        public function updateZoneLastNumber($connection, $zone) {
            $sql = "UPDATE Zones SET LastNumber = LastNumber + 1 WHERE ZoneName = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$zone]);

            if (!$stmt || $stmt->rowCount() <= 0) {
                throw new Exception("Updating Zone's Last number failed: " . $stmt->errorInfo()[2]);
            }
        }
    }
