<?php
    //header('Access-Control-Allow-Origin: *');

    //require_once './connect.inc.php';
    
    class LogicNumbers extends Connect {
        private $remarks;

        public function __construct() {}

        public function fetchLogicNumbers($remarks) {
            //initalize last logic number for reference number from database
            $this->remarks = $remarks;
            $connection = $this->openConnection();
            
            $selectLogicNumber = $connection->prepare("SELECT * FROM logicNumbers WHERE remarks = ?");
            $selectLogicNumber->execute([$this->remarks]);
            $logics = $selectLogicNumber->fetchAll();
            foreach ($logics as $logic) {
                return $logic['number'];
            }
        }
    }

    