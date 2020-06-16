<?php

if (isset($_POST['method'])) {
    if ($_POST['method'] == "openDrawer") {
        $fp = fopen("openDrawer.txt", "w+");
        fwrite($fp, "=C86\n");
        fclose($fp);
        rename("openDrawer.txt", "cassa/TOSEND/openDrawer.txt");
    } else if ($_POST['method'] == "chiusuraFiscale") {
		$fp = fopen("chiusuraFiscale.txt", "w+");
        fwrite($fp, "=C3\n=C10\n=C1");
        fclose($fp);
        rename("chiusuraFiscale.txt", "cassa/TOSEND/chiusuraFiscale.txt");
	}
}
