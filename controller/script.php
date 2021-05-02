<?php
require '../vendor/autoload.php';

use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;
try {
$connector = new NetworkPrintConnector("192.168.1.23", 9100);
    $printer = new Printer($connector);
	
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
	$printer->text("Peppe scemo");
	
        $printer->feed(2);
        $printer->feed(2);
        $printer->feed(2);
		$printer->cut();
		} catch (Exception $e) {
        return false;
    } finally {
        $printer->close();
    }