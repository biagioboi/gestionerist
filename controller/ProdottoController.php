<?php
require_once '../connection.php';
include '../model/Prodotto.php';
include '../model/Categoria.php';

if (isset($_POST['method'])) {
    if ($_POST['method'] == "getProduct") {
        $ret = array();
        foreach($_POST['whatProduct'] as $prod) {
            $ret[$prod] = retriveAllProdsByCategoryName($prod);
        }
        echo json_encode($ret);
    }
}

/**
 * @param $name il nome del prodotto che si vuole cercare
 * @return Prodotto|null il prodotto
 */
function retriveProdByName($name) {
    global $conn;
    $sel = $conn->prepare("SELECT prodotto.nome as nome, prodotto.prezzo as prezzo, categoria.nome as nomeCategoria, categoria.iva as iva FROM prodotto, categoria WHERE prodotto.nome = ? AND prodotto.categoria = categoria.nome");
    $sel -> bind_param('s', $name);
    $sel -> execute();
    $sel -> store_result();
    $prodotto = null;
    while ($row = fetchAssocStatement($sel)) {
        $categoria = new Categoria($row['nomeCategoria'], $row['iva']);
        $prodotto = new Prodotto($row['nome'], $row['prezzo'], $categoria);
    }
    return $prodotto;
}

/**
 * @param $categoria nome della categoria di cui si vogliono sapere i prodotti
 * @return array con i prodotti appartenenti a quella categoria
 */
function retriveAllProdsByCategoryName($categoria) {
    global $conn;
    $sel = $conn->prepare("SELECT prodotto.nome as nome, prodotto.prezzo as prezzo, categoria.nome as nomeCategoria, categoria.iva as iva FROM prodotto, categoria WHERE categoria = ? AND prodotto.categoria = categoria.nome");
    $sel -> bind_param('s', $categoria);
    $sel -> execute();
    $sel -> store_result();
    $arrayProds = array();
    while ($row = fetchAssocStatement($sel)) {
        $categoria = new Categoria($row['nomeCategoria'], $row['iva']);
        $prodotto = new Prodotto($row['nome'], $row['prezzo'], $categoria);
        array_push($arrayProds, $prodotto);
    }
    return $arrayProds;
}

/**
 * @param $prodotto il prodotto da inserire
 * @return bool true se il prodotto è stato inserito con successo, false altrimenti
 */
function addNewProduct($prodotto) {
    global $conn;
    $insert = $conn -> prepare("INSERT INTO prodotto VALUES(?, ?, ?)");
    $name = $prodotto -> getNome();
    $price = $prodotto -> getPrezzo();
    $category = $prodotto -> getCategoria() -> getNome();
    $insert -> bind_param('sds', $name, $price, $category);
    $insert -> execute();
    if ($conn -> errno != 0) {
        return false;
    } else {
        return true;
    }
}
