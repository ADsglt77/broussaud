<?php

namespace App\Controller;

use App\Service\Faire\Faire;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FaireController extends AbstractController 
{
    /**
     * @Route("/faire", name="faire")
    */

    public function index(): Response
    {

        $faire = new Faire();
        $getAllProducts = $faire->getAllProducts();

        dd($getAllProducts);
    }
}