<?php
require_once '../connection.php';
require_once '../model/Tavolo.php';
require_once '../model/Carrello.php';
require_once '../controller/CarrelloController.php';

if (isset($_POST['method'])) {
    if ($_POST['method'] == "getFreeTable") {
        $res = array();
        $res['totaleCarrello'] = $_SESSION['carrello']->totale;
        $res['tavoliLiberi'] = getFreeTable();
        echo json_encode($res);
    } else if($_POST['method'] == "getBusyTable") {
        echo json_encode(getBusyTable());
    } else if($_POST['method'] == "setSessionTable") {
        $_SESSION['tavolo'] = $_POST['tavolo'];
    } else if($_POST['method'] == "removeSessionTable") {
        unset($_SESSION['tavolo']);
    } else if($_POST['method'] == "deleteAllTableContent") {
        freeTable($_POST['tavolo']);
    }
}


/**
 * @return array i numeri dei tavoli liberi
 */
function getFreeTable() {
    global $conn;
    $getFree = $conn -> prepare("SELECT numero FROM tavolo WHERE occupato = 0");
    $getFree -> execute();
    $getFree -> store_result();
    $res = array();
    while ($row = fetchAssocStatement($getFree)) {
        array_push($res, $row);
    }
    return $res;
}

/**
 * @return array i numeri con i relativi coperti dei tavoli occupati
 */
function getBusyTable() {
    global $conn;
    $getFree = $conn -> prepare("SELECT numero, coperti FROM tavolo WHERE occupato = 1");
    $getFree -> execute();
    $getFree -> store_result();
    $res = array();
    while ($row = fetchAssocStatement($getFree)) {
        array_push($res, $row);
    }
    return $res;
}

/**
 * @param $tavolo il numero del tavolo
 * @param $coperti il numero di persone
 */
function setTableAsBusy($tavolo, $coperti) {
    global $conn;
    $update = $conn -> prepare("UPDATE tavolo SET occupato = '1', coperti = ? WHERE numero = ?");
    $update -> bind_param('ds', $coperti, $tavolo);
    $update -> execute();
    echo $conn -> errno;
}


/**
 * @param $tavolo numero del tavolo da liberare
 */
function freeTable($tavolo) {
    global $conn;
    $delete = $conn -> prepare("DELETE FROM prodotto_carrello WHERE carrello IN (SELECT id FROM carrello WHERE tavolo = ?)");
    $delete -> bind_param('s', $tavolo);
    $delete -> execute();
    $delete = $conn -> prepare("DELETE FROM carrello WHERE tavolo = ?");
    $delete -> bind_param('s', $tavolo);
    $delete -> execute();
    $update = $conn -> prepare("UPDATE tavolo SET occupato = 0, coperti = null WHERE numero = ?");
    $update -> bind_param('s', $tavolo);
    $update -> execute();
}

function freeCliente($cliente) {
	 global $conn;
    $delete = $conn -> prepare("DELETE FROM prodotto_carrello WHERE carrello IN (SELECT id FROM carrello WHERE cognome = ?)");
    $delete -> bind_param('s', $cliente);
    $delete -> execute();
    $delete = $conn -> prepare("DELETE FROM carrello WHERE cognome = ?");
    $delete -> bind_param('s', $cliente);
    $delete -> execute();
}
