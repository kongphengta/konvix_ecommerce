<?php

namespace App\Controller;

use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategoryController extends AbstractController
{
    // Affiche les produits d'une catégorie spécifique en fonction de son ID, avec une pagination et une barre de recherche pour filtrer les produits par nom ou description
    #[Route('/category/{id}', name: 'category_index')]
    public function index(Category $category): Response
    {
        // Récupère les produits de la catégorie, par exemple :
        // $products = $category->getProducts();

        return $this->render('category/index.html.twig', [
            'category' => $category,
            // 'products' => $products,
        ]);
    }
}
