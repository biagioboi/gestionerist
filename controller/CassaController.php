<?php

if (isset($_POST['method'])) {
    if ($_POST['method'] == "openDrawer") {
        $fp = fopen("openDrawer.txt", "w+");
        fwrite($fp, "=C86\n");
        fclose($fp);
        rename("openDrawer.txt", "cassa/TOSEND/openDrawer.txt");
    }
}
