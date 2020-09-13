<?php

require_once '../model/ProdottoCarrello.php';
require_once '../model/Carrello.php';
require_once 'ProdottoController.php';
require_once 'TavoloController.php';
require_once 'CassaController.php';
require '../vendor/autoload.php';

use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;

session_start();
if (!isset($_SESSION['carrello'])) {
    $carrello = new Carrello(substr(session_id(), 0, 10));
    $_SESSION['carrello'] = $carrello;
} else {
    $carrello = $_SESSION['carrello'];
}


if (isset($_POST['method'])) {
    if ($_POST['method'] == "addProduct") {
        $prodotto = retriveProdByName($_POST['whatProduct']);
        addProductToCart($carrello, $prodotto);
        echo $_POST['whatProduct'];
    } else if ($_POST['method'] == "decreaseProduct") {
        $prodotto = retriveProdByName($_POST['whatProduct']);
        decreaseProductFromCart($carrello, $prodotto);
        echo $carrello->numProdotti;
    } else if ($_POST['method'] == "numberProductInCart") {
        $return = array();
        $return['numeroProdotti'] = $carrello->numProdotti;
        echo json_encode($return);
    } else if ($_POST['method'] == "newOrder") {
        unset($_SESSION['carrello']);
    } else if ($_POST['method'] == "orderResume") {
        $res = array();
        if (isset($_SESSION['tavolo'])) {
            $res['tavolo'] = $_SESSION['tavolo'];
        }
        $res['carrello'] = $carrello;
        echo json_encode($res);
    } else if ($_POST['method'] == "getProductNote") {
        $prodotto = retriveProdByName($_POST['whatProduct']);
        echo getProductNote($carrello, $prodotto);
    } else if ($_POST['method'] == "setProductNote") {
        $prodotto = retriveProdByName($_POST['whatProduct']);
        $note = $_POST['note'];
        setProductNote($carrello, $prodotto, $note);
    } else if ($_POST['method'] == "addNewOrder") {
        if ($_POST['tavolo'] != null) {
            if (printProductFromCarrello($carrello, $_POST['tavolo'])) {
                addCartToDatabase($carrello, $_POST['tavolo'], $_POST['cognome']);
                setTableAsBusy($_POST['tavolo'], $_POST['coperti']);
            } else {
                return;
            }
        } else {
            if (printProductFromCarrelloAsporto($carrello, $_POST['cognome'])) {
                addCartToDatabase($carrello, $_POST['tavolo'], $_POST['cognome']);
            } else {
                return;
            }
        }
        addAllProductsFromCartToDatabase($carrello, null);
        unset($_SESSION['carrello']);
        session_regenerate_id();
    } else if ($_POST['method'] == "addToExistingOrder") {
        if (!printProductFromCarrello($carrello, $_SESSION['tavolo'])) return;
        addCartToDatabase($carrello, $_SESSION['tavolo'], null);
        addAllProductsFromCartToDatabase($carrello, $_SESSION['tavolo']);
        unset($_SESSION['carrello']);
        unset($_SESSION['tavolo']);
        session_regenerate_id();
    } else if ($_POST['method'] == "retriveAllAsportoOrder") {
        echo json_encode(retriveAsportoOrder());
    } else if ($_POST['method'] == "deleteAsportoOrder") {
        deleteAsportoOrder($_POST['cognome']);
    } else if ($_POST['method'] == "makeBill") {
        if (makeBill($_POST['tavolo'])) {
            echo "ok";
        } else {
            echo "ko";
        }
    } else if ($_POST['method'] == "makeFakeBill") {
        if (makeFakeBill($_POST['tavolo'])) {
            echo "ok";
        } else {
            echo "ko";
        }
    } else if ($_POST['method'] == "makeBillAsporto") {
        if (makeBillAsporto($_POST['cognome'])) {
            echo "ok";
        } else {
            echo "ko";
        }
    } else if ($_POST['method'] == "previewBill") {
        if ($_POST['tavolo'] != null) {
            echo json_encode(getAllProdsFromTableOrCognome($_POST['tavolo'], null));
        } else {
            echo json_encode(getAllProdsFromTableOrCognome(null, $_POST['cognome']));
        }
    } else if ($_POST['method'] == "decreaseProductFromOrder") {
        $prod = retriveProdByName($_POST['prodotto']);
        if ($_POST['tavolo'] != null) {
            decreaseProductFromTableOrCognome($_POST['tavolo'], null, $prod);
        } else {
            decreaseProductFromTableOrCognome(null, $_POST['cognome'], $prod);

        }
    }
}

function makeBill($tavolo)
{
    $res = getAllProdsFromTableOrCognome($tavolo, null);
    $prod = $res['carrello']->prodotti;
    $tosend = array();
    array_push($tosend, "=C1");
    array_push($tosend, "=\"/(     Tavolo n. " . $tavolo . ")");
    array_push($tosend, "=R1/(Coperto)/$100/*" . $res['pax']);
    foreach ($prod as $item) {
        if ($prod->prodotto->prezzo == 0) continue;
        array_push($tosend, "=R1/(" . $item->prodotto->nome . ")/$" . ($item->prodotto->prezzo * 100) . "/*" . $item->quantita);
    }
    array_push($tosend, "=T1");
    if (sendCommand($tosend) != "") {
        freeTable($tavolo);
        return true;
    }
}

function makeFakeBill($tavolo)
{
    $res = getAllProdsFromTableOrCognome($tavolo, null);
    $prod = $res['carrello']->prodotti;
    $numeroTavolo = $res['carrello']->identificativo;
    $variabile = "Tavolo" . $numeroTavolo;
    $tot = 0;
    $connector = new NetworkPrintConnector("192.168.1.7", 9100);
    $printer = new Printer($connector);
    try {
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text("Tavolo " . $tavolo);
        $printer->feed(2);
        $printer->setJustification();
        foreach ($res['carrello']->prodotti as $prod) {
            $tot += $prod->prodotto->prezzo * $prod->quantita;
            if ($prod->prodotto->nome == "barra") {
                $printer->text("----------------------");
                $printer->feed(2);
                continue;
            }
            $printer->setTextSize(1, 1);
            $printer->text($prod->quantita . " x " . $prod->prodotto->nome . "\n\n");
            $printer->selectPrintMode();
            $printer->setTextSize(1, 1);
            $printer->text("Tot: " . number_format($prod->prodotto->prezzo * $prod->quantita, 2) . " euro \n");
            $printer->feed(1);
        }
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text("Totale: " . $tot);
        $printer->feed(2);
        $printer->cut();
    } catch (Exception $e) {
        return false;
    } finally {
        $printer->close();
    }

    return true;

}

function makeBillAsporto($cognome)
{
    $res = getAllProdsFromTableOrCognome(null, $cognome);
    $prod = $res['carrello']->prodotti;
    $cognome = $res['carrello']->identificativo;
    $variabile = "Cliente" . $cognome;
    $fp = fopen($variabile . ".txt", "w+");
    fwrite($fp, "=C1\n");
    fwrite($fp, "=\"/(     Cliente: " . $cognome . ")\n");
    foreach ($prod as $item) {
        if ($item->prodotto->nome == "barra") continue;
        fwrite($fp, "=R1/(" . $item->prodotto->nome . ")/$" . ($item->prodotto->prezzo * 100) . "/*" . $item->quantita . "\n");
    }
    fwrite($fp, "=T1");
    fclose($fp);
    rename($variabile . ".txt", "cassa/TOSEND/" . $variabile . ".txt");
    while (!file_exists("cassa/TOSEND/" . $variabile . ".OK")) continue;
    unlink("cassa/TOSEND/" . $variabile . ".OK");
    $new = fopen("cassa/toDisplay.txt", "w+");
    $tot = $res['carrello']->totale;
    fwrite($new, "=D2/(Totale: " . $tot . " euro)");
    fclose($new);
    sleep(1);
    copy("cassa/toDisplay.txt", "cassa/TOSEND/toDisplay.txt");
    freeCliente($cognome);
    return true;
}

function printProductFromCarrello($carrello, $tavolo)
{
    $connector = new NetworkPrintConnector("192.168.1.7", 9100);
    $printer = new Printer($connector);
    try {
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text("Tavolo " . $tavolo);
        $printer->feed(2);
        $printer->setJustification();
        foreach ($carrello->prodotti as $prod) {
            if ($prod->prodotto->nome == "barra") {
                $printer->setTextSize(2, 2);
                $printer->text("----------------------");
                $printer->selectPrintMode();
                $printer->feed(2);
                continue;
            }
            $printer->setTextSize(1, 2);
            $printer->text($prod->quantita . " x " . $prod->prodotto->nome . "\n");
            if ($prod->note != "") {
                $printer->selectPrintMode();
                $printer->setTextSize(1, 1);
                $printer->text("\n" . $prod->note . "\n");
            }
            $printer->feed(1);
        }
        $printer->text((new DateTime())->format('H:i:s'));
        $printer->feed(1);
        $printer->cut();
    } catch (Exception $e) {
        return false;
    } finally {
        $printer->close();
    }
    return true;
}

function printProductFromCarrelloAsporto($carrello, $cognome)
{
    $connector = new NetworkPrintConnector("192.168.1.7", 9100);
    $printer = new Printer($connector);
    try {
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text("Asporto " . $cognome);
        $printer->feed(2);
        $printer->setJustification();
        foreach ($carrello->prodotti as $prod) {
            if ($prod->prodotto->nome == "barra") {
                $printer->text("----------------------");
                $printer->feed(2);
                continue;
            }
            $printer->setTextSize(3, 2);
            $printer->text($prod->quantita . " x " . $prod->prodotto->nome . "\n");
            if ($prod->note != "") {
                $printer->selectPrintMode();
                $printer->setTextSize(2, 1);
                $printer->text($prod->note . "\n");
            }
            $printer->feed(1);
        }
        $printer->cut();
    } catch (Exception $e) {
        return false;
    } finally {
        $printer->close();
    }
    return true;
}

/**
 * @param $carrello il carrello contenente i prodotti da aggiungere
 * @param $tavolo il tavolo a cui aggiungere i prodotti
 */
function addAllProductsFromCartToDatabase($carrello, $tavolo)
{
    global $conn;
    $identifier = $carrello->identificativo;
    $prodotti = $carrello->prodotti;
    foreach ($prodotti as $item) {
        $nome = $item->prodotto->nome;
        $quantita = $item->quantita;
        $note = $item->note;
        $flag = false;
        if ($tavolo != null) {
            $selectToCheck = $conn->prepare("SELECT * FROM prodotto_carrello WHERE prodotto = ? AND carrello IN (SELECT id FROM carrello WHERE tavolo = ?)");
            $selectToCheck->bind_param('ss', $nome, $tavolo);
            $selectToCheck->execute();
            $selectToCheck->store_result();
            while ($row = fetchAssocStatement($selectToCheck)) {
                $flag = true;
                $update = $conn->prepare("UPDATE prodotto_carrello SET quantita = ?, note = ? WHERE carrello = ? AND prodotto = ?");
                $quantita += $row['quantita'];
                $note = $note . $row['note'];
                $update->bind_param('dsss', $quantita, $note, $row['carrello'], $nome);
                $update->execute();
            }
        }
        if (!$flag) {
            $insert = $conn->prepare("INSERT INTO prodotto_carrello VALUES (null, ?, ?, ?, ?)");
            $insert->bind_param('ssds', $identifier, $nome, $quantita, $note);
            $insert->execute();
        }
    }
}


/**
 * @param $carrello il carrello da aggiungere
 * @param $tavolo il tavolo a cui fa riferimento il carrello
 */
function addCartToDatabase($carrello, $tavolo, $cognome)
{
    global $conn;
    $identifier = $carrello->identificativo;
    if ($tavolo == null) {
        $insert = $conn->prepare("INSERT INTO carrello VALUES(?, null, ?)");
        $insert->bind_param('ss', $identifier, $cognome);
    } else {
        $insert = $conn->prepare("INSERT INTO carrello VALUES(?, ?, null)");
        $insert->bind_param('ss', $identifier, $tavolo);
    }
    $insert->execute();

}

/**
 * @param $carrello il carrello
 * @param $product il prodotto da aggiungere al carrello
 */
function addProductToCart($carrello, $product)
{
    $flag = false;
    foreach ($carrello->prodotti as $item) {
        if ($product->nome == "barra") break;
        if ($item->prodotto == $product && checkIfExistInGroup($carrello, $product)) {
            $item->quantita += 1;
            $carrello->totale = round($carrello->totale + round($product->prezzo, 2), 2);
            $carrello->numProdotti += 1;
            $flag = true;
        }
    }
    if ($flag == false) {
        $prodottoCarrello = new ProdottoCarrello($product);
        $carrello->addProdotto($prodottoCarrello);
        $carrello->totale = round($carrello->totale + round($product->prezzo, 2), 2);
        $carrello->numProdotti += 1;
    }
}

function checkIfExistInGroup($carrello, $product)
{
    $find = false;
    foreach ($carrello->prodotti as $item) {
        if ($item->prodotto->nome == "barra") $find = false;
        if ($item->prodotto->nome == $product->nome) $find = true;
    }
    return $find;
}

/**
 * @param $carrello il carrello
 * @param $product il prodotto da decrementare al carrello
 */
function decreaseProductFromCart($carrello, $product)
{
    $cont = 0;
    foreach ($carrello->prodotti as $item) {
        if ($item->prodotto == $product) {
            if ($item->quantita == 1) {
                array_splice($carrello->prodotti, $cont, 1);
                break;
            } else {
                $item->quantita -= 1;
                break;
            }
        }
        $cont++;
    }
    $carrello->totale -= $product->prezzo;
    $carrello->numProdotti -= 1;
}


/**
 * @param $carrello il carrello
 * @param $product il prodotto di cui si vogliono sapere le note
 * @return mixed le note del prodotto
 */
function getProductNote($carrello, $product)
{
    foreach ($carrello->prodotti as $item) {
        if ($item->prodotto == $product) {
            return $item->note;
        }
    }
}

/**
 * @param $carrello il carrello contenente i prodotti
 * @param $product il prodotto a cui aggiungere la nota
 * @param $note la nota da aggiungere
 */
function setProductNote($carrello, $product, $note)
{
    foreach ($carrello->prodotti as $item) {
        if ($item->prodotto == $product) {
            $item->note = $note;
            return;
        }
    }
}


function deleteAsportoOrder($cognome)
{
    global $conn;
    $delete = $conn->prepare("DELETE FROM prodotto_carrello WHERE carrello IN (SELECT id FROM carrello WHERE cognome = ?)");
    $delete->bind_param('s', $cognome);
    $delete->execute();
    $delete = $conn->prepare("DELETE FROM carrello WHERE cognome = ?");
    $delete->bind_param('s', $cognome);
    $delete->execute();
}

function retriveAsportoOrder()
{
    global $conn;
    $retrive = $conn->prepare("SELECT * FROM carrello WHERE tavolo IS NULL");
    $retrive->execute();
    $retrive->store_result();
    $res = array();
    while ($row = fetchAssocStatement($retrive)) array_push($res, $row);
    return $res;
}

function getAllProdsFromTableOrCognome($tavolo, $cognome)
{
    global $conn;
    if ($tavolo != null) {
        $get = $conn->prepare("SELECT tavolo.coperti AS pax, prodotto_carrello.prodotto AS prodotto, prodotto_carrello.quantita AS quantita , prodotto.prezzo AS prezzo, prodotto.categoria AS categoria FROM prodotto_carrello, prodotto, categoria, carrello, tavolo WHERE carrello IN (SELECT id FROM carrello WHERE carrello.tavolo = ?) AND prodotto_carrello.prodotto = prodotto.nome AND prodotto.categoria = categoria.nome AND tavolo.numero = carrello.tavolo AND carrello.id = prodotto_carrello.carrello");
        $get->bind_param('s', $tavolo);
    } else {
        $get = $conn->prepare("SELECT prodotto_carrello.prodotto AS prodotto, prodotto_carrello.quantita AS quantita , prodotto.prezzo AS prezzo, prodotto.categoria AS categoria FROM prodotto_carrello, prodotto, categoria, carrello WHERE carrello IN (SELECT id FROM carrello WHERE cognome = ?) AND prodotto_carrello.prodotto = prodotto.nome AND prodotto.categoria = categoria.nome AND carrello.id = prodotto_carrello.carrello");
        $get->bind_param('s', $cognome);

    }
    $get->execute();
    $get->store_result();
    $tot = 0;
    $carrello = new Carrello($tavolo . $cognome);
    $pax = 0;
    while ($row = fetchAssocStatement($get)) {
        if ($tavolo != null) $pax = $row['pax'];
        $prodotto = new Prodotto($row['prodotto'], $row['prezzo'], $row['categoria']);
        $prodottoCarrello = new ProdottoCarrello($prodotto);
        $prodottoCarrello->quantita = $row['quantita'];
        $tot = round($tot, 2) + (round($prodotto->prezzo, 2) * $prodottoCarrello->quantita);
        $carrello->addProdotto($prodottoCarrello);
        $carrello->numProdotti += $prodottoCarrello->quantita;
    }
    $carrello->totale = round($tot, 2) + $pax;
    $res = array();
    $res['pax'] = $pax;
    $res['carrello'] = $carrello;
    return $res;
}

function decreaseProductFromTableOrCognome($tavolo, $cognome, $product)
{
    global $conn;
    $nome = $product->nome;
    if ($tavolo != null) {
        $get = $conn->prepare("SELECT quantita, carrello FROM prodotto_carrello WHERE prodotto = ? AND carrello IN (SELECT id FROM carrello WHERE tavolo = ?)");
        $get->bind_param('ss', $nome, $tavolo);
    } else {
        $get = $conn->prepare("SELECT quantita, carrello FROM prodotto_carrello WHERE prodotto = ? AND carrello IN (SELECT id FROM carrello WHERE cognome = ?)");
        $get->bind_param('ss', $nome, $cognome);
    }
    $get->execute();
    $get->store_result();
    while ($row = fetchAssocStatement($get)) {
        $quantita = $row['quantita'];
        if ($quantita == 1) {
            $update = $conn->prepare("DELETE FROM prodotto_carrello WHERE prodotto = ? AND carrello = ?");
            $update->bind_param('ss', $nome, $row['carrello']);
        } else {
            $update = $conn->prepare("UPDATE prodotto_carrello SET quantita = ? WHERE prodotto = ? AND carrello = ?");
            $quantita--;
            $update->bind_param('dss', $quantita, $nome, $row['carrello']);
        }
        $update->execute();
    }
}
