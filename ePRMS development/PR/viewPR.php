<?php
     //header('Access-Control-Allow-Origin: *');

     include_once "../includes/class-autoload.inc.php";

     $viewDocuments = new DocumentView();
     echo json_encode($viewDocuments->displayDocuments());