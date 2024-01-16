<?php
include ('db_connection/dbconnection.php');
error_reporting(E_ALL);
date_default_timezone_set('Europe/London');
include_once('Classes/PHPExcel/IOFactory.php');
include_once('Classes/PHPExcel.php');

/*check point*/

// Read the existing excel file
$objReader = PHPExcel_IOFactory::createReader('Excel2007');
$objPHPExcel = $objReader->load('excel/Book1.xlsx');

// Update it's data
// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);

//$sheet = $objPHPExcel->getActiveSheet();

//Variables
$a=1;
//*START*//

$servername = "localhost";
$username = "root";
$password = "srwd95";
$dbname = "hris";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$pinfo = "SELECT * FROM tbl_leave ORDER BY id ASC";
$result = mysqli_query($conn,$pinfo);
$rowcount = mysqli_num_rows($result);
$objPHPExcel->setActiveSheetIndex(1);
while($row = mysqli_fetch_array($result)) {
$emp_id = $row['emp_id'];
$period = $row['period'];
$month = $row['month'];
$year = $row['year'];
$particulars = $row['particulars'];
$days = $row['days'];
$hours = $row['hours'];
$minutes = $row['minutes'];

if ($row['vl_earned'] == "")	{
$vl_earned = 0;
}
else	{
$vl_earned = $row['vl_earned'];
}

if ($row['vl_WiP'] == "")	{
$vl_wip = 0;
}
else	{
$vl_wip = $row['vl_WiP'];
}

if ($row['vl_WoP'] == "")	{
$vl_wop = 0;
}
else	{
$vl_wop = $row['vl_WoP'];
}

if ($row['vl_balance'] == "")	{
$vl_balance = 0;
}
else	{
$vl_balance = $row['vl_balance'];
}

if ($row['sl_earned'] == "")	{
$sl_earned = 0;
}
else	{
$sl_earned = $row['sl_earned'];
}

if ($row['sl_WiP'] == "")	{
$sl_wip = 0;
}
else	{
$sl_wip = $row['sl_WiP'];
}

if ($row['sl_WoP'] == "")	{
$sl_wop = 0;
}
else	{
$sl_wop = $row['sl_WoP'];
}

if ($row['sl_balance'] == "")	{
$sl_balance = 0;
}
else	{
$sl_balance = $row['sl_balance'];
}

$remarks = $row['remarks'];

$whole = "INSERT INTO [hris].[dbo].[Leave] (emp_id,period,month,year,particulars,days,hours,minutes,vl_earned,vl_wip,vl_wop,vl_balance,sl_earned,sl_wip,sl_wop,sl_balance,remarks,type) VALUES ('$emp_id','$period','$month','$year','$particulars','$days','$hours','$minutes',$vl_earned,$vl_wip,$vl_wop,$vl_balance,$sl_earned,$sl_wip,$sl_wop,$sl_balance,'$remarks','1')";

$objPHPExcel->getActiveSheet()
			->setCellValue('A'.$a, $whole)
			;
$a++;
}

//*END*//

// Generate an updated excel file
// Redirect output to a client’s web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="PR'.$prno.'.xlsx"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');