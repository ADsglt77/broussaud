<?php

namespace App\Service\Shopify;

use Shopify\Context;
use Shopify\Auth\FileSessionStorage;
use Shopify\Auth\Session;
use Shopify\Clients\Graphql;
use App\Service\Shopify\ShopifyOrder;
use App\Service\Shopify\ShopifyItem;

class Shopify
{
  private $_apiKey = null;
  private $_apiSecret = null;
  private $_shop = null;
  protected $_locationId = null;
  private $_apiVersion = '2024-01';
  protected $_url = '';
  public $_session = null;

  public function __construct(string $shop = 'maison-broussaud')
  {
    $this->connexionShop($shop);
    $this->setSession();
  }

  private function connexionShop(string $shop)
  {
    $shopList = [
      'maison-broussaud' => array(
        'apiKey' => '', // ICI SE TROUVE L'API KEY DE L'ENTREPRISE
        'apiSecret' => '', // ICI SE TROUVE L'API SECRET DE L'ENTREPRISE
        'shop' => 'maison-broussaud',
        'id_location' => 1234, // ICI SE TROUVE L'ID DE L'ENTREPRISE
      )
    ];
    $this->_apiKey = $shopList[$shop]['apiKey'];
    $this->_apiSecret = $shopList[$shop]['apiSecret'];
    $this->_shop = $shopList[$shop]['shop'];
    $this->_locationId = $shopList[$shop]['id_location'];

    $sessionPath = $_SERVER['DOCUMENT_ROOT'] . 'temp' . DIRECTORY_SEPARATOR . 'shopify_api_sessions';

    Context::initialize(
      $this->_apiKey,
      $this->_apiSecret,
      'read_products',
      'http://127.0.0.1:8000',
      new FileSessionStorage($sessionPath),
      $this->_apiVersion,
      false,
      false,
    );
  }

  private function setSession()
  {
    $session = new Session(
      id: 'NA',
      shop: $this->_shop,
      isOnline: false,
      state: 'NA'
    );

    $session->setAccessToken($this->_apiSecret);
    $this->_session = $session;
  }

  public function getClient()
  {
    return new Graphql(
      $this->_session->getShop(),
      $this->_session->getAccessToken()
    );
  }

  public function listeCommandes()
  {
    $date = new \DateTime();
    $date = $date->modify('-2 week')->format('Y-m-d\TH:i:sO');

    $query = <<<QUERY
    query {
        orders(first: 250, query: "created_at:>=$date", sortKey: CREATED_AT, reverse: true) {
          edges {
            node {
              id
              createdAt
              cancelReason
              displayFinancialStatus
              displayFulfillmentStatus
              paymentGatewayNames
              name
              email
              discountCode
              tags
              note
              currentTotalPriceSet {
                shopMoney {
                  amount
                  currencyCode
                }
              }
              subtotalPriceSet {
                shopMoney {
                  amount
                }
              }
              currentTotalTaxSet {
                shopMoney {
                  amount
                }
              }
              customer {
                displayName
                email
                phone
              }
              billingAddress {
                address1
                city
                country
                phone
                zip
              }
              shippingLines(first: 250) {
                edges {
                  node {
                    id
                    title
                    discountedPriceSet {
                      shopMoney {
                        amount
                      }
                    }
                    taxLines {
                      ratePercentage
                      title
                    }
                  }
                }
              }
              cartDiscountAmountSet {
                presentmentMoney {
                  amount
                }
              }
              lineItems (first: 250) {
                edges {
                  node {
                    id
                    title
                    quantity
                    originalUnitPriceSet {
                      shopMoney {
                        amount
                      }
                    }
                    discountedUnitPriceSet {
                      shopMoney {
                        amount
                      }
                    }
                    sku
                    variant {
                      id
                      title
                      product {
                        id
                        title
                      }
                    }
                    taxable
                    taxLines {
                      ratePercentage
                    }
                  }
                }
              }
            }
          }
        }
      }
    QUERY;

    $client = $this->getClient();
    $response = $client->query($query);
    return $this->processCommande($response->getDecodedBody());
  }




  private function processCommande($response): array
  {
    $commandeList = [];
    $orders = $response['data']['orders']['edges'];

    foreach ($orders as $orderEdge) {
      $order = $orderEdge['node'];

      $SO = new ShopifyOrder();
      $SO->shop = $this->_shop;
      $SO->id = $order['id'];
      $SO->created_at = $order['createdAt'];
      $SO->cancel_reason = $order['cancelReason'];
      $SO->financial_status = $order['displayFinancialStatus'];
      $SO->fulfillment_status = $order['displayFulfillmentStatus'];
      $SO->gateway = $order['paymentGatewayNames'];
      $SO->name = $order['name'];
      $SO->email = $order['email'];
      $SO->discount_code = $order['discountCode'];
      $SO->tags = $order['tags'];
      $SO->note = $order['note']; 

      $SO->total_price = floatval($order['currentTotalPriceSet']['shopMoney']['amount']);
      $SO->subtotal_price = floatval($order['subtotalPriceSet']['shopMoney']['amount']);
      $SO->total_tax = floatval($order['currentTotalTaxSet']['shopMoney']['amount']);

      $SO->setCustomer($order['customer']);
      $SO->setCustomer($order['customer']['displayName']);
      $SO->setCustomer($order['customer']['email']);
      $SO->setCustomer($order['customer']['phone']);

      $SO->billing_address = $order['billingAddress'];
      $SO->billing_address1 = $order['billingAddress']['address1'];
      $SO->billing_city = $order['billingAddress']['city'];
      $SO->billing_country = $order['billingAddress']['country'];
      $SO->billing_phone = $order['billingAddress']['phone'];
      $SO->billing_zip = $order['billingAddress']['zip'];

      foreach ($order['lineItems']['edges'] as $itemEdge) {
        $item = $itemEdge['node'];
        $SI = new ShopifyItem();
        $SI->id = $item['id'];
        $SI->price = floatval($item['originalUnitPriceSet']['shopMoney']['amount']);
        $SI->product_id = $item['variant']['product']['id'];
        $SI->variant_id = $item['variant']['id'];
        $SI->quantity = $item['quantity'];
        $SI->title = $item['title'];
        $SI->sku = $item['sku'];
        $SI->tax_rate = isset($item['taxLines'][0]['ratePercentage']) ? $item['taxLines'][0]['ratePercentage'] / 100 : 0;
        
        $SI->prepareItem();
        $SO->line_items[] = $SI;
      }

      $SO->prepareOrder();
      $commandeList[] = $SO;
    }

    return $commandeList;
  }

}