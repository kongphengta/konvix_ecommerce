<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FooterController extends AbstractController
{
    #[Route('/conditions-utilisation', name: 'app_conditions')]
    public function conditions(): Response
    {
        return $this->render('footer/conditions.html.twig');
    }

    #[Route('/mentions-legales', name:  'app_mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('footer/mentions_legales.html.twig');
    }

    #[Route('/qui-sommes-nous', name:  'app_about')]
    public function about(): Response
    {
        return $this->render('footer/about.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('footer/contact.html.twig');
    }
}