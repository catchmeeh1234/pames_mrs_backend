<?php
    spl_autoload_register("myAutoLoader");

    function myAutoLoader($className) {
        $url = $_SERVER['HTTP_POST'].$_SERVER['REQUEST_URI'];
        if (strpos($url, 'Accounts') !== false || strpos($url, 'Employees') !== false || strpos($url, 'Notifications') !== false || strpos($url, 'Divisions') !== false || strpos($url, 'PR') !== false || strpos($url, 'Units') !== false) {
            $path = '../classes/';
        } else {
            $path = 'classes/';
        }
        $extension = ".class.php";

        //echo $path . $className . $extension . "<br>";
        require_once $path . $className . $extension;

    }

    