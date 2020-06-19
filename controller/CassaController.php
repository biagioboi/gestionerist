<?php

if (isset($_POST['method'])) {
    $fp = fsockopen("127.0.0.1", 1000, $errno, $errstr, 30);
    if (!$fp) {
        $result["result"] = "error";
        echo json_encode($result);
    }
    if ($_POST['method'] == "openDrawer") {
        $out = "{action: 'apriCassetto', content: [], header: []}" . PHP_EOL;
    } else if ($_POST['method'] == "chiusuraFiscale") {
        $out = "{action: 'chiusuraFiscale', content: [], header: []}" . PHP_EOL;
    }
    fwrite($fp, $out);
    while (!feof($fp)) {
        $out = fgets($fp, 128);
    }
    fclose($fp);
}
