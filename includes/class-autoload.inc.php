<?php

    spl_autoload_register("myAutoLoader");

    function myAutoLoader($className) {
       
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestURI = $_SERVER['REQUEST_URI'];

        $url = $requestMethod.$requestURI;
        if (
                strpos($url, 'UserAccounts') !== false || 
                strpos($url, 'Consumers') !== false || 
                strpos($url, 'Zones') !== false || 
                strpos($url, 'Bills') !== false || 
                strpos($url, 'MeterReaders') !== false ||
                strpos($url, 'RateSchedules') !== false ||
                strpos($url, 'Discounts') !== false ||
                strpos($url, 'ReadingSettings') !== false ||
                strpos($url, 'Charges') !== false || 
                strpos($url, 'LogicNumbers') !== false ||
                strpos($url, 'Announcements') !== false ||
                strpos($url, 'ScheduleCharges') !== false ||
                strpos($url, 'OfficialReceipt') !== false ||
                strpos($url, 'Authentication') !== false ||
                strpos($url, 'Units') !== false
            ) {
            $path = '../classes/';
        } else {
            $path = 'classes/';
        }
        $extension = ".class.php";

        //echo $path . $className . $extension . "<br>";
        require_once $path . $className . $extension;

    }

    