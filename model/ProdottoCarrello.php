<?php


class ProdottoCarrello
{

    public function __construct($prodotto) {
        $this->prodotto = $prodotto;
        $this->quantita = 1;
        $this->note = "";
    }



    public function getProdotto(){
        return $this->prodotto;
    }

    public function setProdotto($prodotto)
    {
        $this->prodotto = $prodotto;
    }

    public function getQuantita() {
        return $this->quantita;
    }

    public function setQuantita($quantita) {
        $this->quantita = $quantita;
    }

    public function getNote() {
        return $this->note;
    }

    public function setNote($note) {
        $this->note = $note;
    }

    public $prodotto;
    public $quantita;
    public $note;
}
