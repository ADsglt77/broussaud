<?php
namespace App\Service\Shopify;

class ShopifyItem
{
    public $id = null;
    public $price = 0; // prix unitaire ttc de l'article
    public $product_id = null;
    public $variant_id = null;
    public $quantity = 0;
    public $title = null;
    public $sku = null; // EAN13 de l'article à la taille/co
    public $tax_rate = 0; // taux de la TVA $article['tax_lines'][0]['rate']


    /**
     * @return null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @return null
     */
    public function getProductId()
    {
        return $this->product_id;
    }

    /**
     * @return null
     */
    public function getVariantId()
    {
        return $this->variant_id;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return null
     */
    public function getTitle()
    {
        return str_replace(" (la carte de sélection s'affiche après le paiement)", '', $this->title);
    }

    /**
     * @return null
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @return float
     */
    public function getTaxRate(): float
    {
        return $this->tax_rate;
    }

    /**
     * @return float
     */
    public function getPuht(): float
    {
        return $this->price / (1 + $this->tax_rate);
    }

    /**
     * @return float
     */
    public function getMontantTva(): float
    {
        return $this->tax_rate * $this->price / (1 + $this->tax_rate);
    }



    public function prepareItem()
    {
        $this->checkSku();
    }

    private function checkSku()
    {
        if($this->sku!==null){
            if('FAIRE-COMMISSION'===$this->sku) $this->sku = 'COMMISSION';

            $a = explode('/',$this->sku);
            $count = count($a);
            if($count>1){
                $this->sku = $a[ $count-1 ];
            }

//            if(13!==strlen($this->sku)) $this->sku = 'SKU-LENGHT-KO';
        }
    }


}