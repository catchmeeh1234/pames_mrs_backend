<!DOCTYPE html>
<html lang="en">

<head>
<link rel="stylesheet" type="text/css" href="css/eprmsprint.css">
<!--<link rel="stylesheet" type="text/css" media="print" href="css/eprmsprint.css" />-->
</head>
<style>
  .container-container {
    display: flex;
    justify-content: space-between;
  }

  .left-text {
    order: 1;
  }

  .right-text {
    order: 2;
  }
</style>

<!--<body onload="myFunction()">-->
<body>

<?php

require_once 'connect.php';

$prno = $_GET['prno'];
//$prno = "23-04-0130";
$totalamount = 0;
$total = 0;
$x = 9;
$countx = 0;

$sql ="SELECT pr_no, pr_dateCreated, pr_requestor, pr_designation, pr_division, pr_purpose, pr_title FROM Pr_details WHERE pr_no='$prno'";

$stmta = sqlsrv_query( $conn, $sql );
if( $stmta === false) {
    die( print_r( sqlsrv_errors(), true) );
}

while( $row = sqlsrv_fetch_array( $stmta, SQLSRV_FETCH_ASSOC) ) {
$prno = $row['pr_no'];
$pr_dateCreated = date('m/d/Y',strtotime($row['pr_dateCreated']));
$pr_requestor = $row['pr_requestor'];
$pr_division = $row['pr_division'];
$pr_purpose = $row['pr_purpose'];
$title = $row['pr_title'];

if ($row['pr_designation'] == "Water Resources Facilities Operator A") {
$pr_designation = "WRFO-A";
}
elseif ($row['pr_designation'] == "Water Resources Facilities Operator B") {
$pr_designation = "WRFO-B";
}
elseif ($row['pr_designation'] == "Water Resources Facilities Operator C") {
$pr_designation = "WRFO-C";
}
elseif ($row['pr_designation'] == "Senior Water Maintenance Man A") {
$pr_designation = "Sr. Water Maintenance Man A";
}
elseif ($row['pr_designation'] == "Senior Water Maintenance Man B") {
$pr_designation = "Sr. Water Maintenance Man B";
}
elseif ($row['pr_designation'] == "Senior Water Maintenance Man C") {
$pr_designation = "Sr. Water Maintenance Man C";
}
elseif ($row['pr_designation'] == "SENIOR CORPORATE ACCOUNTS ANALYST") {
$pr_designation = "Sr. Corporate Accounts Analyst";
}
elseif ($row['pr_designation'] == "ADMINISTRATION SERVICES ASSISTANT C") {
$pr_designation = "ADMIN. SERVICES ASSISTANT C";
}
else {
$pr_designation = $row['pr_designation'];
}

}

$sql2 ="SELECT emp_id FROM Pr_Employees WHERE full_name='$pr_requestor'";

$stmt2 = sqlsrv_query( $conn, $sql2 );
if( $stmt2 === false) {
    die( print_r( sqlsrv_errors(), true) );
}

while( $row2 = sqlsrv_fetch_array( $stmt2, SQLSRV_FETCH_ASSOC) ) {
$sig = $row2['emp_id'].".png";
}

$approver ="SELECT FullName, Designation FROM UserAccounts WHERE Access='Approver' AND Division='$pr_division'";

$stmt = sqlsrv_query( $conn, $approver );
if( $stmt === false) {
    die( print_r( sqlsrv_errors(), true) );
}

while( $rowa = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC) ) {
$fullname = $rowa['FullName'];
$designation = $rowa['Designation'];
}
?>

<table class='border' width='773px' border='1' align='center'>

<tr class='borderless_bottom' height='21px'><td align='center' colspan='7'><b>PURCHASE REQUEST</b></td></tr>
<tr class='borderless_bottom' height='25px'><td align='center' colspan='7'>SANTA ROSA (NE) WATER DISTRICT</td></tr>
<tr class='borderright_bottom' height='22px'><td align='center' colspan='7'>Agency</td></tr>

<tr class='borderlessright_bottom' height='12px'>
<td width='74px'></td>
<td width='74px'></td>
<td width='168px'></td>
<td width='153px'></td>
<td width='110px'></td>
<td width='84px'></td>
<td width='110px'></td>
</tr>

<tr class='borderlessright_bottom' height='21px'>

<td colspan='3'>Division:<u> 
  <?php
  if ($pr_division == 'ENGINEERING AND MAINTENANCE') {
    echo "<font size='2.5'>".$pr_division."</font>";
  }
  else {
    echo "<font size='3'>".$pr_division."</font>";
  }
  
  ?> 
</u></td>

<td colspan='2'>PR No.:<u> <?php echo $prno; ?> </u></td>
<td colspan='2'>Date:<u> <?php echo $pr_dateCreated; ?> </u></td>
</tr>

<tr class='borderlessright_bottom' height='21px'>
<td colspan='3'>Section:___________________</td>
<td colspan='2'>SAI No.:________</td>
<td colspan='2'>Date:__________</td>
</tr>

<tr class='borderright_bottom' height='12px'>
<td colspan='3'></td>
<td colspan='2'></td>
<td colspan='2'></td>
</tr>

<tr class='border_bottom' height='43px'>
<td align='center'>Stock No.</td>
<td align='center'>Unit</td>
<td align='center' colspan='2'>Item Description</td>
<td align='center'>Quantity</td>
<td align='center'>Unit Cost</td>
<td align='center'>Total Cost</td>
</tr>

<?php

if ($title == '') {}
else {
echo "<tr class='borderless_bottom' height='26'>";
echo "<td align='center'></td>";
echo "<td align='center'></td>";
echo "<td align='left' style='padding : 0 0 0 5px;' colspan='2'><b>".$title."</b></td>";
echo "<td align='center'></td>";
echo "<td align='right'></td>";
echo "<td align='right'</td>";
echo "</tr>";
}

$sql2 ="SELECT * FROM Pr_item_details WHERE pr_no='$prno'";

$stmtb = sqlsrv_query( $conn, $sql2 );
if( $stmtb === false) {
    die( print_r( sqlsrv_errors(), true) );
}

$params = array();
$options =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
$stmtcount = sqlsrv_query($conn, $sql2,$params,$options);
$count = sqlsrv_num_rows ($stmtcount);

while( $row2 = sqlsrv_fetch_array( $stmtb, SQLSRV_FETCH_ASSOC) ) {
$total = ($row2['pr_cost'] * $row2['pr_quantity']);
$totalamount = $totalamount + $total;
$itemid = $row2['prItems_id'];
$bold_text = $row2['bold_text'];

if ($row2['pr_quantity'] > 1)   {
    $plural = 's';
}
else    {
    $plural = '';
}

if ($row2['pr_unit'] == 'Kilogram') {
    $unit = "kg".$plural.".";
}
elseif ($row2['pr_unit'] == 'Linear Meter') {
    $unit = "lin. m.";
}
elseif ($row2['pr_unit'] == 'Square Meter') {
    $unit = "sq. m.";
}
elseif ($row2['pr_unit'] == 'Square Foot') {
    $unit = "sq. ft.";
}
elseif ($row2['pr_unit'] == 'Foot') {
    $unit = "ft.";
}
elseif ($row2['pr_unit'] == 'Piece') {
    $unit = "pc".$plural.".";
}
elseif ($row2['pr_unit'] == 'Liter') {
    $unit = "ltr".$plural.".";
}
elseif ($row2['pr_unit'] == 'Meter') {
    $unit = "mtr".$plural.".";
}
elseif ($row2['pr_unit'] == 'Box') {
  if ($row2['pr_quantity'] > 1)   {
    $plural = 'es';
  }
  else    {
    $plural = '';
  }
    $unit = "box".$plural;
}
elseif ($row2['pr_unit'] == 'Inch') {
    $unit = "in.";
}
elseif ($row2['pr_unit'] == '-') {
    $unit = "";
}
else    {
    $unit = strtolower($row2['pr_unit']).$plural;
}

echo "<tr class='borderless_bottom' height='26'>";
echo "<td align='center'></td>";
echo "<td align='center'>".$unit."</td>";

if ($bold_text == 'false') {
  echo "<td align='left' style='padding : 0 0 0 5px;' colspan='2'>".$row2['pr_items']."</td>"; 
}
elseif ($bold_text == 'true') {
  echo "<td align='left' style='padding : 0 0 0 5px;' colspan='2'><b>".$row2['pr_items']."</b></td>"; 
}


if ($row2['pr_quantity'] == 0) {
  echo "<td align='center'></td>";
}
else {
  echo "<td align='center'>".$row2['pr_quantity']."</td>";
}

if ($row2['pr_cost'] == 0) {
  echo "<td align='right' style='padding : 0 5px 0 0;'></td>";
}
else {
  echo "<td align='right' style='padding : 0 5px 0 0;'>".number_format($row2['pr_cost'], 2, '.', ',')."</td>";
}

if ($total == 0) {
  echo "<td align='right' style='padding : 0 5px 0 0;'></td>";
}
else {
  echo "<td align='right' style='padding : 0 5px 0 0;'>".number_format($total, 2, '.', ',')."</td>";
}

echo "</tr>";

/*subitem*/
$sql3 ="SELECT * FROM Pr_subitem_details WHERE prItems_id='$itemid'";

$stmtc = sqlsrv_query( $conn, $sql3 );
if( $stmtc === false) {
    die( print_r( sqlsrv_errors(), true) );
}

$params1 = array();
$options1 =  array( "Scrollable" => SQLSRV_CURSOR_KEYSET );
$stmtcount1 = sqlsrv_query($conn, $sql3,$params1,$options1);
$countz = sqlsrv_num_rows ($stmtcount1);

$count1 =  $count1 + $countz;

while( $row3 = sqlsrv_fetch_array( $stmtc, SQLSRV_FETCH_ASSOC) ) {
$total2 = ($row3['dpr_cost'] * $row3['dpr_quantity']);
$totalamount = $totalamount + $total2;

if ($row3['dpr_quantity'] > 1)   {
    $plural = 's';
}
else    {
    $plural = '';
}

if ($row3['dpr_unit'] == 'Kilogram') {
    $unit = "kg".$plural.".";
}
elseif ($row3['dpr_unit'] == 'Linear Meter') {
    $unit = "lin. m.";
}
elseif ($row3['dpr_unit'] == 'Square Meter') {
    $unit = "sq. m.";
}
elseif ($row3['dpr_unit'] == 'Square Foot') {
    $unit = "sq. ft.";
}
elseif ($row3['dpr_unit'] == 'Foot') {
    $unit = "ft.";
}
elseif ($row3['dpr_unit'] == 'Piece') {
    $unit = "pc".$plural.".";
}
elseif ($row3['dpr_unit'] == 'Liter') {
    $unit = "ltr".$plural.".";
}
elseif ($row3['dpr_unit'] == 'Meter') {
    $unit = "mtr".$plural.".";
}
elseif ($row3['dpr_unit'] == 'Box') {
  if ($row3['dpr_quantity'] > 1)   {
    $plural = 'es';
  }
  else    {
    $plural = '';
  }
    $unit = "box".$plural;
}
elseif ($row3['dpr_unit'] == 'Inch') {
    $unit = "in.";
}
elseif ($row3['dpr_unit'] == '-') {
    $unit = "";
}
else    {
    $unit = strtolower($row3['dpr_unit']).$plural;
}

echo "<tr class='borderless_bottom'>";
echo "<td align='center'></td>";
echo "<td align='center'>".$unit."</td>";
echo "<td align='left' style='padding : 0 0 0 15px;' colspan='2'>".$row3['dpr_items']."</td>";

if ($row3['dpr_quantity'] == 0) {
  echo "<td align='center'></td>";
}
else {
  echo "<td align='center'>".$row3['dpr_quantity']."</td>";
}

if ($row3['dpr_cost'] == 0) {
  echo "<td align='right' style='padding : 0 5px 0 0;'></td>";
}
else {
  echo "<td align='right' style='padding : 0 5px 0 0;'>".number_format($row3['dpr_cost'], 2, '.', ',')."</td>";
}

if ($total2 == 0) {
  echo "<td align='right' style='padding : 0 5px 0 0;'></td>";
}
else {
  echo "<td align='right' style='padding : 0 5px 0 0;'>".number_format($total2, 2, '.', ',')."</td>";
}

echo "</tr>";
}
/*subitem*/

}

$countx = $count + $count1;
//echo $countx.'<br>'.$count.'<br>'.$count1;

if ($countx > 9) {

}
else {
$totalrows = $x - $countx;
//echo $totalrows." ".$count." ".$x;
for ($y = 1; $y <= $totalrows; $y++) {
    echo "<tr class='borderless_bottom' height='26'>";
    echo "<td align='center'></td>";
    echo "<td align='center'></td>";
    echo "<td align='center' colspan='2'></td>";
    echo "<td align='center'></td>";
    echo "<td align='right'></td>";
    echo "<td align='right'></td>";
    echo "</tr>";
}
}

?>

<tr class='border_bottom' height='26px'>
<td align='center'></td>
<td align='center'></td>
<td align='center' colspan='2'></td>
<td align='center'></td>
<td align='right' style='padding : 0 5px 0 0;'><b>Total</b></td>
<td align='right' style='padding : 0 5px 0 0;'><b><?php echo number_format($totalamount, 2, '.', ','); ?></b></td>
</tr>

<tr class='borderless_bottom' height='21px'>
<td colspan='7'>Purpose: <u><?php echo $pr_purpose; ?></u></td>
</tr>

<tr class='borderright_bottom' height='22px'>
<td colspan='7'></td>
</tr>
<!--
<tr class='borderless_bottom' height='21px'>
<td colspan='2' width='124px'></td>
<td align='center' colspan='2' width='309px'>Requested by:</td>
<td align='center' colspan='3' width='276px'>Approved by:</td>
</tr>

<tr class='borderless_bottom' height='21px'>
<td colspan='2'><b>Signature</b></td>
<td align='center' colspan='2'></td>
<td align='center' colspan='3'></td>
</tr>

<tr class='borderless_bottom'  height='21px'>
<td colspan='2' width='124px'><b>Printed Name:</b></td>
<td align='center' colspan='2'><b><?php echo strtoupper($pr_requestor); ?></b></td>
<td align='center' colspan='3'><b>ENGR. JOEL FELIX H. BERNARDO</b></td>
</tr>

<tr class='borderless_bottom'  height='21px'>
<td colspan='2' width='124px'><b>Designation:</b></td>
<td align='center' colspan='2'><font size='2.5'><?php echo strtoupper($pr_designation); ?></font></td>
<td align='center' colspan='3'><font size='2.5'>GENERAL MANAGER</font></td>
</tr>

<tr class='borderless_bottom' height='22px'>
<td colspan='2'></td>
<td align='center' colspan='2'></td>
<td align='center' colspan='3'></td>
</tr>


<tr>
<td colspan='2'></td>
<td colspan='2' width='309px'></td>
<td colspan='3' width='276px'>
<div class="container">
  <img src="img/admin2.png" alt="Snow" style="width:100%;">
  <div class="centered">Approved by:<br><br><b>ENGR. JOEL FELIX H. BERNARDO</b><br>General Manager</div>
</div>
</td>
</tr>
-->
<tr class='borderless_bottom' height='21px'>
<td colspan='2'></td>
<td align='center' colspan='2'>Requested by:</td>
<td align='center' colspan='3'>Approved by:</td>
</tr>

<tr class='borderless_bottom' height='21px'>
<td colspan='2' width='124px'><b>Signature</b></td>
<td align='center' colspan='2' rowspan='4'>
<div class="container2">
  <img src="img/signatures/<?php echo $sig; ?>" alt="Snow" style="width: 115px; margin: -25px 0 0 0;" />
  <div class="centered2"><b><?php echo strtoupper($pr_requestor); ?></b><br><font size='3'><?php echo strtoupper($pr_designation); ?></font></div>
</div>
</td>
<td align='center' colspan='3' rowspan="4" width='276px'>
<div class="container">
  <?php
      if ($pr_division == 'ADMINISTRATIVE SERVICES' OR $pr_division == 'Administrative Services')    {
        echo '<img src="img/admin.png" alt="Snow" style="width:295px; margin: 25px 0 0 0;">';
      }
      elseif ($pr_division == 'ENGINEERING AND MAINTENANCE' OR $pr_division == 'Engineering and Maintenance')    {
        echo '<img src="img/em.png" alt="Snow" style="width:295px; margin: 25px 0 0 0;">';
      }
      elseif ($pr_division == 'FINANCE AND COMMERCIAL' OR $pr_division == 'Finance and Commercial')   {
        echo '<img src="img/fc.png" alt="Snow" style="width:295px; margin: 25px 0 0 0;">';
      }
      elseif ($pr_division == 'PRODUCTION' OR $pr_division == 'Production')   {
        echo '<img src="img/prod.png" alt="Snow" style="width:295px; margin: 25px 0 0 0;">';
      }
  ?>
  
  <div class="centered"><b>ENGR. JOEL FELIX H. BERNARDO</b><br><font size='3'>GENERAL MANAGER</font></div>
</div>
</td>
</tr>

<tr class='borderless_bottom' height='21px'>
<td colspan='2' width='124px'><b>Printed Name:</b></td>
</tr>

<tr class='borderless_bottom'  height='21px'>
<td colspan='2' width='124px'><b>Designation:</b></td>
</tr>

<tr class='borderless_bottom' height='22px'>

</tr>

</table>
<div class="container-container">
<span class="left-text">AO 6/15/02</span>
<span class="right-text">--date printed--<?php echo date("F j, Y h:i:s A"); ?>--</span>
</div>

<hr style='margin: 0 0 0 0'>

<script>
window.print();
settimeout(window.close, 0);
</script>

</body>
</html>