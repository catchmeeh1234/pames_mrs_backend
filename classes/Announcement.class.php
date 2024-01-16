<?php

    class Announcement extends Connect {

        public function __construct() {}

        public function updateAnnouncementMessage($message) {
            $connection = $this->openConnection();

            $sql = "UPDATE tblannouncement SET Announce = ? WHERE AnnounceID = 1";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$message]);
            
            $count = $stmt->rowCount();

            if ($count == 0 || !$stmt) {
                return ["status" => "error updating announcement " . $stmt->errorInfo()[2]];
            } else {
                return ["status" => "Announcement updated"];
            }
        }
        public function updateAnnouncementContactNo($contactNo) {
            $connection = $this->openConnection();

            $sql = "UPDATE tblannouncement SET Announce = ? WHERE AnnounceID = 2";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$contactNo]);
            
            $count = $stmt->rowCount();

            if ($count == 0 || !$stmt) {
                return ["status" => "error updating announcement " . $stmt->errorInfo()[2]];
            } else {
                return ["status" => "Announcement updated"];
            }
        }  
        public function viewAnnouncement() {
            $connection = $this->openConnection();

            $sql = "SELECT * FROM tblannouncement";
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0 || !$stmt) {
                return [];
            } else {
                return $result;
            }
        }  
    }
