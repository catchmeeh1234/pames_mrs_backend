<?php
    //header('Access-Control-Allow-Origin: *');

   //include_once "./includes/class-autoload.inc.php";
   //include_once "./../includes/class-autoload.inc.php";
   include_once "../includes/class-autoload.inc.php";

   $object = new User();
   echo json_encode($object->fetchAllUserAccounts());
