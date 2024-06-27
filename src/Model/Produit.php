<?php
namespace App\Model;

class Produit
{
    private string $id;
    private string $libelle;
    private string $couleur;
    private array $varianteList = array();

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;
        return $this;
    }
    public function addVariante(Variante $variante): self
    {
        $this->varianteList[] = $variante;
        return $this;
    }
}

