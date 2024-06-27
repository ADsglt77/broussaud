<?php
namespace App\Service\Shopify;

class ShopifyOrder
{
    public $shop = null;
    public $id = null;
    public $created_at = null; // "created_at": "2023-11-27T21:47:47+01:00"
    public $cancel_reason = null;
    public $financial_status = null; // paid, partially_refunded, refunded
    public $fulfillment_status = null; // fulfilled, partial, null (non traité)
    /**
     * gateway depraciated => use payment_gateway_names
     * @var null
     */
    public $gateway = null; // moyen de paiement : stripe, paypal, manual
    public $name = null; // numéro de la facture #1001
    public $subtotal_price = 0.0; // total TTC des articles
    public $total_price = 0.0; // total TTC de la facture frais de port inclus
    public $total_tax = 0.0; // montant TVA
    public $line_items = array();
    public $shipping_lines = array();
    public $country_code = null;
    public $tags = null;
    public $email = null;
    public $nom = null;
    public $prenom = null;
    public $total_discounts = 0.0;
    public $discount_codes = array();
    public $tax_lines = array();
    public $note_attributes = array();

    /**
     * @return null
     */
    public function getShop()
    {
        return $this->shop;
    }

    /**
     * @return null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return null
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @return null
     */
    public function getCancelReason()
    {
        return $this->cancel_reason;
    }

    /**
     * @return null
     */
    public function getFinancialStatus()
    {
        return $this->financial_status;
    }

    /**
     * @return null
     */
    public function getFulfillmentStatus()
    {
        return $this->fulfillment_status;
    }

    /**
     * @return null
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    /**
     * @return null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return float
     */
    public function getSubtotalPrice(): float
    {
        return $this->subtotal_price;
    }

    /**
     * @return float
     */
    public function getTotalPrice(): float
    {
        return $this->total_price;
    }

    /**
     * @return float
     */
    public function getTotalTax(): float
    {
        return $this->total_tax;
    }

    /**
     * @return array
     */
    public function getLineItems(): array
    {
        return $this->line_items;
    }

    /**
     * @return float
     */
    public function getMontantHt(): float
    {
        return $this->total_price - $this->total_tax;
    }

    /**
     * @return null
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

    public function getNoteAttributes(): array
    {
        return $this->note_attributes;
    }

    public function getMessage()
    {
        if(empty($this->note_attributes)) return '';
//        "note_attributes": [
//            {
//                "name": "message",
//                "value": "Tr\u00e8s joyeux anniversaire notre Empereur.\nPlein de bises des Mar\u00e9chaux G\u00e9g\u00e9 et Philippe"
//            }
//        ]
        return array_reduce($this->note_attributes, function ($msg, $e){
            if('note'===$e->name) $msg.= 'Note interne : ';
            if('message'===$e->name) $msg.= 'Message du client à écrire sur une carte : ';
            return $msg.= $e->value;
//            return $msg.= utf8_encode($e->value);
        });
    }

    public function prepareOrder()
    {
        $this->formatDate();
        $this->checkFinancialStatus();
        $this->checkFulfillmentStatus();
        $this->checkGetaway();
        $this->checkShippingLines();
        $this->checkDiscount();
    }

    /**
     * @return null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return null
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * @return null
     */
    public function getPrenom()
    {
        return $this->prenom;
    }

    /**
     * Formatage de la date 2022-03-22T07:36:28+01:00
     * vers 2022-03-22 07:36:28
     */
    private function formatDate()
    {
        if($this->created_at!==null){
            $this->created_at = str_replace(array('T','+'), ' ', $this->created_at);
            $this->created_at = explode(' ', $this->created_at);
            $this->created_at = $this->created_at[0] .' '. $this->created_at[1];
        }
    }

    private function checkFinancialStatus()
    {
        if($this->financial_status!==null){
            $statusList = array('paid', 'partially_refunded', 'refunded', 'pending');
            if(!in_array($this->financial_status, $statusList)) $this->financial_status = 'erreur statut ('. $this->financial_status .')';
        }
    }

    private function checkFulfillmentStatus()
    {
        if($this->fulfillment_status!==null){
            $statusList = array('fulfilled', 'partial');
            if(!in_array($this->fulfillment_status, $statusList)) $this->fulfillment_status = 'erreur statut ('. $this->fulfillment_status .')';
        }
    }

    private function checkGetaway()
    {
        // gateway depraciated => use payment_gateway_names
        $gateway = $this->gateway;
        if($gateway===null || empty($gateway)) return;

        $this->gateway = $gateway[0];
        $statusList = array('gift_card', 'shopify_payments', 'stripe', 'paypal', 'manual');
        if(!in_array($this->gateway, $statusList)) $this->gateway = 'erreur gateway ('. $this->gateway .')';

        if($this->tags!==null){
            if('faire-webshop'===$this->tags){
                $this->gateway = $this->tags;
//                $this->shop = $this->tags; RISQUE DE CONFLIT
            }
        }
    }

    private function checkShippingLines()
    {
        if(!empty($this->shipping_lines)){

            if(! isset($this->shipping_lines[0])) return;

            $price = floatval($this->shipping_lines[0]->price);
            $taxeRate = 0;
            if(isset($this->shipping_lines[0]->tax_lines[0])) $taxeRate = floatval($this->shipping_lines[0]->tax_lines[0]->rate);

            // si frais de port offert alors on n'ajoute pas de ZPORT
            if(0===$taxeRate && 0.0===$price) return;

            // identification ZPORT avec ou sans TVA
            $skuPort = 'ZPORT';
            if(0===$taxeRate && 0<$price) $skuPort = 'ZPORTSANSTVA';

            $SI = new ShopifyItem();
            $SI->price = $price;
            $SI->quantity = 1;
            $SI->tax_rate = $taxeRate;
            $SI->title = $this->shipping_lines[0]->title;
            $SI->sku = $skuPort;
            $this->line_items[] = $SI;
        }
    }

    public function setCustomer($customer){
        if(!empty($customer->email)) $this->email = $customer->email;
        else $this->email = 'email inconnu';
        if(!empty($customer->last_name)) $this->nom = $customer->last_name;
        else $this->email = '';
        if(!empty($customer->first_name)) $this->prenom = $customer->first_name;
        else $this->email = '';
    }

    public function checkDiscount()
    {
        if(0.0===floatval($this->total_discounts) && empty($this->discount_codes)) return;

        // on exclus les frais de port offerts car déjà calculé avec $this->checkShippingLines()
        $discountList = array_filter($this->discount_codes, function ($e){
            return $e->type != 'shipping';
        });

        $codeList = array();
        foreach($discountList as $d) $codeList[] = $d->code;

        $discount = array_reduce($discountList, function ($discount, $e){
            return $discount + floatval($e->amount);
        });

        $taxeRate = 0;
        if(isset($this->tax_lines[0])) $taxeRate = floatval($this->tax_lines[0]->rate);

        $SI = new ShopifyItem();
        $SI->price = $discount;
        $SI->quantity = 1;
        $SI->tax_rate = $taxeRate;
        $SI->title = 'Code(s) : '. join(' | ', $codeList);
        $SI->sku = 'ZREMISECOMMERCIALE';
        $this->line_items[] = $SI;
    }

    public function checkGiftCard()
    {

    }
}