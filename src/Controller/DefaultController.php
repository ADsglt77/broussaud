<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route("/default", name="app_default")
     */
    public function index(): Response
    {
        return $this->render('default/index.html.twig', [
            'controller_name' => 'Adrien',
            'age' => '18 ans',
        ]);

                /*
        $chuck = file_get_contents('https://api.chucknorris.io/jokes/random');
        $api1 = json_decode($chuck);

        $img = file_get_contents('https://dog.ceo/api/breeds/image/random');
        $api2 = json_decode($img);        

        $quote = $api1->value;
        $image = $api2->message;

        return $this->render('dev/test.html.twig', [
            'quote' => $quote,
            'img' => $image,
        ]);
        */

        /*
        $demonSlayer = file_get_contents('https://demon-slayer-api.onrender.com/v1/');
        $api = json_decode($demonSlayer, true);

        $personnages = [];
        foreach ($api as $profil) {
            $personnages[] = $profil;
        }

        return $this->render('dev/test.html.twig', [
            'profil' => $personnages,
        ]);
        */
    }
}
