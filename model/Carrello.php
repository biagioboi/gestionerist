<?php


class Carrello   {


    public function __construct($identificativo) {
        $this->identificativo = $identificativo;
        $this->totale = 0;
        $this->numProdotti = 0;
        $this->prodotti = array();
    }
    public function getNumProdotti() {
        return $this->numProdotti;
    }
    public function setNumProdotti($numProdotti) {
        $this->numProdotti = $numProdotti;
    }
    public function getIdentificativo() {
        return $this->identificativo;
    }

    public function setIdentificativo($identificativo) {
        $this->identificativo = $identificativo;
    }

    public function getTotale() {
        return $this->totale;
    }

    public function setTotale($totale) {
        $this->totale = $totale;
    }

    public function getProdotti() {
        return $this->prodotti;
    }

    public function setProdotti($prodotti) {
        $this->prodotti = $prodotti;
    }

    public function addProdotto($prodotto) {
        array_push($this->prodotti, $prodotto);
    }

    public $numProdotti;
    public $identificativo;
    public $totale;
    public $prodotti;

}
