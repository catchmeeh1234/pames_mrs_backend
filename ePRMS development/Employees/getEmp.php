<?php
    //header('Access-Control-Allow-Origin: *');

   //include_once "./includes/class-autoload.inc.php";
   //include_once "./../includes/class-autoload.inc.php";
   include_once "../includes/class-autoload.inc.php";

   $division = strtoupper($_GET['division']);

   $object = new Employee();
   echo json_encode($object->getEmployees($division));