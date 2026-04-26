<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_sitemap', defaults: ['_format' => 'xml'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();

        $urls = [];

        // Pages statiques
        $staticPages = [
            ['route' => 'app_home',            'priority' => '1.0', 'changefreq' => 'daily'],
            ['route' => 'product_list',         'priority' => '0.9', 'changefreq' => 'daily'],
            ['route' => 'app_about',            'priority' => '0.5', 'changefreq' => 'monthly'],
            ['route' => 'app_contact',          'priority' => '0.5', 'changefreq' => 'monthly'],
            ['route' => 'app_conditions',       'priority' => '0.3', 'changefreq' => 'yearly'],
            ['route' => 'app_mentions_legales', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        foreach ($staticPages as $page) {
            $urls[] = [
                'loc'        => $this->generateUrl($page['route'], [], UrlGeneratorInterface::ABSOLUTE_URL),
                'priority'   => $page['priority'],
                'changefreq' => $page['changefreq'],
                'lastmod'    => date('Y-m-d'),
            ];
        }

        // Produits validés
        $products = $em->getRepository(Product::class)->findBy(['isValidated' => true]);
        foreach ($products as $product) {
            $urls[] = [
                'loc'        => $this->generateUrl('app_product_show', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'priority'   => '0.8',
                'changefreq' => 'weekly',
                'lastmod'    => date('Y-m-d'),
            ];
        }

        // Catégories
        $categories = $em->getRepository(Category::class)->findAll();
        foreach ($categories as $category) {
            $urls[] = [
                'loc'        => $this->generateUrl('product_list', ['category' => $category->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'priority'   => '0.7',
                'changefreq' => 'weekly',
                'lastmod'    => date('Y-m-d'),
            ];
        }

        $response = new Response(
            $this->renderView('sitemap/index.xml.twig', ['urls' => $urls]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/xml']
        );

        return $response;
    }
}
