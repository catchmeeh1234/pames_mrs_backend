<?php

    class Discount extends Connect {

        public function __construct() {}

        public function fetchDiscount($discount_name) {
            $connection = $this->openConnection();

            if ($discount_name === "All") {
                $sql = "SELECT * FROM Discounts";
                $stmt = $connection->prepare($sql);
                $stmt->execute();
            } else {
                $sql = "SELECT * FROM Discounts WHERE DiscountName = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$discount_name]);
            }

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }   
    }
