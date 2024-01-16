<!DOCTYPE html>
<html lang="en">

<head>
<link rel="stylesheet" type="text/css" href="css/eprmsprint.css">
</head>

<body onload="myFunction()">
<!--<body>-->

<?php
//$serverName = "192.168.10.17\\sqlexpress, 1433"; //serverName\instanceName, portNumber (default is 1433)
$serverName = "localhost\\sqlexpress, 1433"; //serverName\instanceName, portNumber (default is 1433)

$connectionInfo = array( 'Database'=>'ePRMS_test', 'UID'=>'sa', 'PWD'=>'p@$$w0rd', 'CharacterSet' => 'UTF-8', 'TrustServerCertificate' => 'True');
$conn = sqlsrv_connect( $serverName, $connectionInfo);

if( !$conn ) 
{
     echo "Connection could not be established.<br />";
     die( print_r( sqlsrv_errors(), true));
}

$prno = $_GET['prno'];
//$prno = "23-04-0130";
$totalamount = 0;
$total = 0;
$x = 9;

$sql ="SELECT pr_no, pr_dateCreated, pr_requestor, pr_designation, pr_division, pr_purpose FROM Pr_details WHERE pr_no='$prno'";

$stmta = sqlsrv_query( $conn, $sql );
if( $stmta === false) {
    die( print_r( sqlsrv_errors(), true) );
}

while( $row = sqlsrv_fetch_array( $stmta, SQLSRV_FETCH_ASSOC) ) {
$prno = $row['pr_no'];
$pr_dateCreated = date('m/d/Y',strtotime($row['pr_dateCreated']));
$pr_requestor = $row['pr_requestor'];
$pr_designation = $row['pr_designation'];
$pr_division = $row['pr_division'];
$pr_purpose = $row['pr_purpose'];
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
<td colspan='3'></td>
<td colspan='2'></td>
<td colspan='2'></td>
</tr>

<tr class='borderlessright_bottom' height='21px'>
<td colspan='3'>Division:<u> <?php echo $pr_division; ?> </u></td>
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
<td align='center' width='74px'>Stock No.</td>
<td align='center' width='74px'>Unit</td>
<td align='center' width='168px'>Item Description</td>
<td align='center' colspan='2'>Quantity</td>
<td align='center' width='84px'>Unit Cost</td>
<td align='center' width='110px'>Total Cost</td>
</tr>

<?php
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
else    {
    $unit = strtolower($row2['pr_unit']).$plural;
}

echo "<tr class='borderless_bottom' height='26'>";
echo "<td align='center'></td>";
echo "<td align='center'>".$unit."</td>";
echo "<td align='left' style='padding : 0 0 0 5px;'>".$row2['pr_items']."</td>";
echo "<td align='center' colspan='2'>".$row2['pr_quantity']."</td>";
echo "<td align='right' style='padding : 0 5px 0 0;'>".number_format((float)$row2['pr_cost'], 2, '.', '')."</td>";
echo "<td align='right' style='padding : 0 5px 0 0;'>".number_format((float)$total, 2, '.', '')."</td>";
echo "</tr>";
}

if ($count > 9) {

}
else {
$totalrows = $x - $count;
//echo $totalrows." ".$count." ".$x;
for ($y = 1; $y <= $totalrows; $y++) {
    echo "<tr class='borderless_bottom' height='26'>";
    echo "<td align='center'></td>";
    echo "<td align='center'></td>";
    echo "<td align='center'></td>";
    echo "<td align='center' colspan='2'></td>";
    echo "<td align='right'></td>";
    echo "<td align='right'></td>";
    echo "</tr>";
}
}

?>

<tr class='border_bottom' height='26px'>
<td align='center'></td>
<td align='center'></td>
<td align='center'></td>
<td align='center' colspan='2'></td>
<td align='right' style='padding : 0 5px 0 0;'><b>Total</b></td>
<td align='right' style='padding : 0 5px 0 0;'><b><?php echo number_format((float)$totalamount, 2, '.', ''); ?></b></td>
</tr>

<tr class='borderless_bottom' height='21px'>
<td colspan='7'>Purpose: <u><?php echo $pr_purpose; ?></u></td>
</tr>

<tr class='borderright_bottom' height='22px'>
<td colspan='7'></td>
</tr>

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

</table>
AO 6/15/02
<hr style='margin: 0 0 0 0'>

<script>
//window.print();
//setTimeout(window.close, 0);
</script>

</body>
</html>