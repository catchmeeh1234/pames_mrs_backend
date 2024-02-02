<?php

    class Discount extends Connect {
        private $authInstance;

        public function __construct() {
            $this->authInstance = new Auth();
        }

        public function fetchDiscount($discount_name) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

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
