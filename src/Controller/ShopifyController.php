<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Shopify\Shopify;

class ShopifyController extends AbstractController
{
    /**
     * @Route("/shopify", name="app_shopify")
     */
    public function index(): Response
    {
        $Shopify = new Shopify();
        
        $ListCommande = $Shopify->listeCommandes();
        
        dd($ListCommande);

        return $this->render('shopify/shopify.html.twig', [
            'getShop' => $Shopify,
            'listeCommandes' => $ListCommande,
        ]);
    }
}