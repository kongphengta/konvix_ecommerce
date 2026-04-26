<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

final class FaviconController extends AbstractController
{
    #[Route('/favicon.ico', name: 'app_favicon_ico', methods: ['GET'])]
    public function faviconIco(): RedirectResponse
    {
        return $this->redirect('/favicon.svg', 301);
    }
}
