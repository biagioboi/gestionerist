<?php


class Prodotto{

    public function __construct($nome, $prezzo, $categoria) {
        $this->prezzo = round($prezzo, 2);
        $this->nome = $nome;
        $this->categoria = $categoria;
    }

    public function getNome() {
        return $this->nome;
    }

    public function setNome($nome) {
        $this->nome = $nome;
    }

    public function getPrezzo() {
        return $this->prezzo;
    }

    public function setPrezzo($prezzo) {
        $this->prezzo = round($prezzo, 2);
    }

    public function getCategoria() {
        return $this -> categoria;
    }

    public function setCategoria($categoria) {
        $this->categoria = $categoria;
    }


    public $nome;
    public $prezzo;
    public $categoria;
}
