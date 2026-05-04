<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategoryController extends AbstractController
{
    // Affiche les produits d'une catégorie spécifique en fonction de son ID, avec une pagination et une barre de recherche pour filtrer les produits par nom ou description
    #[Route('/category/{id}', name: 'category_index')]
    public function index(Category $category): Response
    {
        $breadcrumbs = [
        ['label' => 'Accueil', 'route' => 'app_home'],
        ['label' => 'Catégories', 'route' => 'category_list'],
        ['label' => $category->getName(), 'route' => null],
        ];

        // Récupère les produits de la catégorie, par exemple :
        $products = $category->getProducts();

        return $this->render('category/index.html.twig', [
            'category' => $category,
            'breadcrumbs' => $breadcrumbs,
            'products' => $products,
        ]);
    }

    #[Route('/categories', name: 'category_list')]
    public function list(CategoryRepository $repo): Response
    {
        $categories = $repo->findAll();

        $breadcrumbs = [
        ['label' => 'Accueil', 'route' => 'app_home'],
        ['label' => 'Catégories', 'route' => null],
        ];

        return $this->render('category/list.html.twig', [
        'categories' => $categories,
        'breadcrumbs' => $breadcrumbs,
    ]);
}
}
