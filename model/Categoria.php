<?php


class Categoria {


    public function __construct($nome, $iva) {
        $this->nome = $nome;
        $this->iva = $iva;
    }

    public function getNome() {
        return $this->nome;
    }

    public function setNome($nome) {
        $this->nome = $nome;
    }

    public function getIva() {
        return $this->iva;
    }

    public function setIva($iva) {
        $this->iva = $iva;
    }


    public $nome;
    public $iva;
}
