<?php


if (isset($_POST['method'])) {
    if ($_POST['method'] == "openDrawer") {
        sendCommand(["=C86"]);
    } else if ($_POST['method'] == "chiusuraFiscale") {
        sendCommand(["=C3", "=C10"]);
    }
}

if (isset($_GET['method'])) {
    if ($_GET['method'] == "printScontrino") {
        $toSend = $_GET['commands'];
	sendCommand($toSend);
    }
}

function sendCommand($command)
{
    $toSend = "";
    foreach ($command as $cmd) {
        $toSend .= "<cmd>" . $cmd . "</cmd>";
    }
    $xml = '<?xml version="1.0" encoding="ISO-8859-1"?>
<Service>' . $toSend .
        '</Service>';
    echo $xml;
    $url = 'http://192.168.1.10/service.cgi';
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($curl);
    if (curl_errno($curl)) {
        throw new Exception(curl_error($curl));
    }
    curl_close($curl);
    echo $result;

}