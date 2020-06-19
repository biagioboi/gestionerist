<?php

require_once '../model/ProdottoCarrello.php';
require_once '../model/Carrello.php';
require_once 'ProdottoController.php';
require_once 'TavoloController.php';

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
        addCartToDatabase($carrello, $_POST['tavolo'], $_POST['cognome']);
        if ($_POST['tavolo'] != null) {
            setTableAsBusy($_POST['tavolo'], $_POST['coperti']);
            printProductFromCarrello($carrello, $_POST['tavolo']);
        } else {
            printProductFromCarrelloAsporto($carrello, $_POST['cognome']);
        }
        addAllProductsFromCartToDatabase($carrello, null);
        unset($_SESSION['carrello']);
        session_regenerate_id();
    } else if ($_POST['method'] == "addToExistingOrder") {
        addCartToDatabase($carrello, $_SESSION['tavolo'], null);
        addAllProductsFromCartToDatabase($carrello, $_SESSION['tavolo']);
        printProductFromCarrello($carrello, $_SESSION['tavolo']);
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
    $fp = fsockopen("127.0.0.1", 1000, $errno, $errstr, 30);
    if (!$fp) {
        return false;
    }
    $result["action"] = "stampaScontrino";
    $result["content"] = [];
    $result["header"] = ["'Tavolo n. " . $tavolo . "'"];
    array_push($result["content"], sprintf("{name: 'Coperto', quantity: %d, price: 100, numRep: 1}", $res['pax']));
    foreach ($prod as $item) {
        array_push($result["content"], sprintf("{name: '%s', quantity: %d, price: %d, numRep: 1}", $item->prodotto->nome, $item->quantita, $item->prodotto->prezzo * 100));
    }
    fwrite($fp, createStringFromResult($result));
    while (!feof($fp)) {
        $print_return = fgets($fp, 128);
    }
    fclose($fp);
    $print_return = json_decode($print_return);
    if ($print_return -> {'result'} == "success") {
        freeTable($tavolo);
        return true;
    } else {
        return false;
    }
}

function makeBillAsporto($cognome)
{
    $res = getAllProdsFromTableOrCognome(null, $cognome);
    $prod = $res['carrello']->prodotti;
    $fp = fsockopen("127.0.0.1", 1000, $errno, $errstr, 30);
    if (!$fp) {
        return false;
    }
    $result["action"] = "stampaScontrino";
    $result["content"] = [];
    $result["header"] = ["'Cliente: " . $cognome . "'"];
    foreach ($prod as $item) {
        array_push($result["content"], sprintf("{name: '%s', quantity: %d, price: %d, numRep: 1}", $item->prodotto->nome, $item->quantita, $item->prodotto->prezzo * 100));
    }
    fwrite($fp, createStringFromResult($result));
    while (!feof($fp)) {
        $print_return = fgets($fp, 128);
    }
    fclose($fp);
    $print_return = json_decode($print_return);
    if ($print_return -> {'result'} == "success") {
        freeCliente($cognome);
        return true;
    } else {
        return false;
    }
}

function printProductFromCarrello($carrello, $tavolo)
{
    //TODO istruzioni per la cassa per stampare l'ordine
}

function printProductFromCarrelloAsporto($carrello, $cognome)
{
    //TODO istruzioni per la cassa per stampare l'ordine
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
                $update->bind_param('dss', $quantita, $note, $row['carrello'], $nome);
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
        if ($item->prodotto == $product) {
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


function createStringFromResult($result) {
    $strToPrint = "{";
    foreach ($result as $key => $value) {
        if ($key != "content" and $key != "header") {
            $strToPrint .= $key . ":'" . $value . "',";
        } else {
            $strToPrint .= $key . ":[";
            foreach ($value as $val) {
                $strToPrint .= $val . ",";
            }
            $strToPrint = substr($strToPrint, 0, -1);
            $strToPrint .= "],";
        }
    }
    $strToPrint = substr($strToPrint, 0, -1);
    return $strToPrint."}".PHP_EOL;
}
