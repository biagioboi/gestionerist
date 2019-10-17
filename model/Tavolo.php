<?php


class Tavolo
{
    public function __construct($numero, $occupato) {
        $this->numero = $numero;
        $this->occupato = $occupato;
        $this->coperti = 0;
    }

    public function getNumero() {
        return $this->numero;
    }

    public function setNumero($numero) {
        $this->numero = $numero;
    }

    public function getOccupato() {
        return $this->occupato;
    }

    public function setOccupato($occupato) {
        $this->occupato = $occupato;
    }

    public function getNumCoperti() {
        return $this->numCoperti;
    }

    public function setNumCoperti($numCoperti) {
        $this->numCoperti = $numCoperti;
    }




    public $numero;
    public $occupato;
    public $numCoperti;


}
