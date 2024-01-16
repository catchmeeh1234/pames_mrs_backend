<?php
    include_once "../includes/class-autoload.inc.php";

    $medicine_id = $_GET['medicine_id'];

    $obj = new Medicine();
    echo json_encode($obj->loadMedicineHistory($medicine_id));