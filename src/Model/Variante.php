<?php
namespace App\Model;

class Variante
{
    private string $id;
    private string $ean;
    private float $prix; // 16.00
    private int $qteStock;

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setEan(string $ean): self
    {
        $this->ean = $ean;
        return $this;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function setQteStock(int $qteStock): self
    {
        $this->qteStock = $qteStock;
        return $this;
    }
}
