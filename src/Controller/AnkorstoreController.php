<?php

namespace App\Controller;

use App\Service\Ankorstore\Ankorstore;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Model\Produit;
use App\Model\Variante;

class AnkorstoreController extends AbstractController
{
    /**
     * @Route("/ankorstore", name="ankorstore")
     */
    public function index(): Response
    {

        $ankorstore = new Ankorstore();
        $catalogue = $ankorstore->getCatalogue();

        $produitsList = [];
        foreach ($catalogue as $product) {
            $produit = new Produit();
            $produit->setId($product->id);
            $produit->setLibelle($product->attributes->name);
            
            if (isset($product->included)) {
                foreach($product->included as $variant) {
                    $variante = new Variante();
                    $variante->setId($variant->id);
                    $variante->setEan($variant->attributes->sku);
                    $variante->setPrix($variant->attributes->retailPrice);
                    $variante->setQteStock($variant->attributes->availableQuantity);
    
                    $produit->addVariante($variante);
                }
            }
            
            $produitsList[] = $produit;
        }

        dd($produitsList);
        
    }
}
