<?php
require __DIR__ . '/vendor/autoload.php'; // Adjust this path according to your project structure

use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;
//use Mike42\Escpos\PrintConnectors\FilePrintConnector;

try {
    // $ip_address = '192.168.10.37'; // Replace with the printer's IP address
    // $connector = new NetworkPrintConnector($ip_address, 9100); // 9100 is the default port for many printers
    // $printer = new Printer($connector);

    // // Send ESC/POS commands for printing
    // $printer->text("Hello, World!\n");
    // // You can send various ESC/POS commands here using the methods provided by the library
    // // For example:
    // // $printer->initialize();
    // // $printer->text("Some text here");
    // // $printer->cut();

    // $printer->close();

    $connector = new WindowsPrintConnector("smb://192.168.10.37/resibo");
    //$connector = new DummyPrintConnector();

    $printer = new Printer($connector);
    // Convert Uint8Array data to a string
    $printer->initialize();

    $printer->feed();

    $printer->setTextSize(1, 2);

    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->setEmphasis(true);

    $printer->text("Republic of the Philippines\n");
    $printer->text("Pantabangan Municipal Electric System\n");

    $printer->setEmphasis(false);

    $printer->text("Barangay East Poblacion\n");
    $printer->text("Pantabangan, Nueva Ecija\n");

    $printer->feed();

    $printer->setEmphasis(true);
    $printer->text("WATER BILL\n");
    $printer->setEmphasis(false);

    $printer->feed();

    $printer->initialize();
    $printer->text("Account Information         Bill NO: 1\n");
    $printer->text("Account No  :   01-00001-1\n");
    $printer->text("Name        :     Juan Miguel Salonga\n");
    $printer->text("Address     :     Santa Rosa, Nueva Ecija\n");
    $printer->text("Class       :     Residential\n");
    $printer->text("Meter No    :     1-0145\n");
    $printer->text("Ave. Cons   :     10\n");
    $printer->feed();

    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->setEmphasis(true);
    $printer->text("BILLING DETAILS\n");
    $printer->setEmphasis(false);

    $printer->feed();

    $printer->initialize();
    $printer->text("Previous Reading :   10\n");
    $printer->text("Current Reading  :   20\n");
    $printer->text("Consumption      :   10\n");
    $printer->feed();

    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->setEmphasis(true);
    $printer->text("PERIOD COVERED\n");
    $printer->setEmphasis(false);

    $printer->feed();

    $printer->initialize();
    $printer->text("FROM            |       TO\n");
    $printer->text("10/10/2023      |       11/10/2023\n");
    $printer->feed();

    $printer->text("Due Date        |       Disconnection Date\n");
    $printer->text("11/20/2023      |       11/21/2023\n");

    $printer->feed();

    $printer->text("---------------------------------------");
    $printer->feed();
    $printer->feed();
    $printer->text("BILLING SUMMARY        |       AMOUNT\n");
    $printer->feed();
    $printer->text("Current Billing        |       400.00\n");
    $printer->text("Current Billing        |       400.00\n");
    $printer->text("Senior Citizen Disc    |       8.00\n");
    $printer->feed();
    $printer->text("Total Amount Due       |       392.00\n");
    $printer->text("Surcharge(11/30/23)    |       60.00\n");
    $printer->feed();
    $printer->text("---------------------------------------");
    $printer->feed();
    $printer->text("Amount After Due       |       440.00\n");
    $printer->text("---------------------------------------");
    $printer->feed();
    $printer->feed();

    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->setEmphasis(true);
    $printer->text("OCTOBER 2023\n");
    $printer->text("PLEASE PAY BEFORE DUE DATE\n");
    $printer->setEmphasis(false);
    $printer->text("Please visit our website:\n");
    $printer->text("pantabanganwater.gov.ph\n");
    $printer->feed();
    $printer->feed();

    $printer->qrCode("01-00001-1", null, 6);

    $printer->feed();
    $printer->feed();

    $printer->cut();
    
    // Get the buffered output
    //$escposOutput = $connector->getData();

    $printer -> close();


    // Convert ESC/POS to HTML representation
    // $htmlOutput = '<div class="receipt">';
    // $lines = explode("\n", $escposOutput);
    // foreach ($lines as $line) {
    //     $htmlOutput .= '<div class="receipt-line">' . htmlspecialchars($line) . '</div>';
    // }
    // $htmlOutput .= '</div>';


    // $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    // $pdf->SetCreator('Your Creator');
    // $pdf->SetAuthor('Your Author');
    // $pdf->SetTitle('Receipt');
    // $pdf->SetSubject('Receipt');
    // $pdf->SetMargins(10, 10, 10);
    // $pdf->SetFont('times', '', 12);
    // $pdf->AddPage();
    // $pdf->writeHTML($htmlOutput);
    // $pdf->Output('D:\php-htdocs\PAMES\backend-PAMES\receipt.pdf', 'F'); // Output to a file named 'receipt.pdf'

} catch (\Exception $e) {
    echo "Print failed: " . $e->getMessage();
}
?>