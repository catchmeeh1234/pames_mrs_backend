<?php
    include_once "../includes/class-autoload.inc.php";

    $itemInfo = $_POST['itemInfo'];

    $obj = new Medicine();
    echo json_encode($obj->editMedicineInfo($itemInfo));