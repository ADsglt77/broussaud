<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestDevController extends AbstractController
{
    /**
     * @Route("/test", name="app_default")
     */
    public function index(): Response
    {
        $valorant = file_get_contents('https://valorant-api.com/v1/agents/');
        $api = json_decode($valorant);

        $agents = [];
        foreach ($api as $agents) {
            $agent[] = $agents;
        }

        return $this->render('dev/test.html.twig', [
            'agents' => $agents,
        ]);
        
    }
}
