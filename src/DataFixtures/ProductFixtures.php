<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Seller;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création de l'utilisateur pour le vendeur 'konvix'
        $user = new \App\Entity\User();
        $user->setEmail('serviceclient@konvix.com');
        $user->setPassword(password_hash('password', PASSWORD_BCRYPT));
        $user->setRoles(['ROLE_SELLER']);
        $manager->persist($user);

        // Création du vendeur 'konvix'
        $seller = new Seller();
        $seller->setUser($user);
        $seller->setName('konvix');
        $seller->setDescription('Premier vendeur du site');
        $seller->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($seller);

        // Création de la catégorie 'Tech'
        $category = new Category();
        $category->setName('Tech');
        $category->setDescription('Produits technologiques');
        $category->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($category);

        // Crée 2 produits de démonstration
        $productsData = [
            [
                'name' => 'Produit 1',
                'description' => 'Description du produit 1',
                'price' => 81.19,
                'imageFolder' => 'CMMERCEDES70BM',
                'mainImage' => '/uploads/CMMERCEDES70BM/CMMERCEDES70BM_1.jpg',
                'stock' => 10,
            ],
            [
                'name' => 'Produit 2',
                'description' => 'Description du produit 2',
                'price' => 94.85,
                'imageFolder' => 'CMMERCEDES75N',
                'mainImage' => '/uploads/CMMERCEDES75N/CMMERCEDES75N_1.jpg',
                'stock' => 5,
            ],
        ];

        foreach ($productsData as $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setDescription($data['description']);
            $product->setPrice($data['price']);
            $product->setCreatedAt(new \DateTimeImmutable());
            $product->setSeller($seller);
            $product->setCategory($category);
            $product->setImageFolder($data['imageFolder']);
            $product->setMainImage($data['mainImage']);
            $product->setStock($data['stock']);
            $manager->persist($product);
        }

        $manager->flush();
    }
}
