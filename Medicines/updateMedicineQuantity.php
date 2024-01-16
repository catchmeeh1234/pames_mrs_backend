<?php
    include_once "../includes/class-autoload.inc.php";

    $medicineInfo = $_POST['medicineInfo'];

    $obj = new Medicine();
    echo json_encode($obj->updateMedicineQuantity($medicineInfo));