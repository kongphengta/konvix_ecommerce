<?php

namespace App\DataFixtures;


use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Category;
use App\Entity\User;
use App\Entity\Seller;
use App\Entity\Product;
use App\Entity\ProductImage;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Catégories
        $categories = [];
        foreach ([
            ['name' => 'Informatique', 'description' => 'Ordinateurs, accessoires, etc.'],
            ['name' => 'Vêtements', 'description' => 'Mode et accessoires.'],
        ] as $catData) {
            $category = new Category();
            $category->setName($catData['name']);
            $category->setDescription($catData['description']);
            $category->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($category);
            $categories[] = $category;
        }

        // Utilisateurs et vendeurs
        $sellers = [];
        for ($i = 1; $i <= 2; $i++) {
            $user = new User();
            $user->setEmail("vendeur$i@email.com");
            $user->setPassword(password_hash('password', PASSWORD_BCRYPT));
            $user->setRoles(['ROLE_SELLER']);
            $manager->persist($user);

            $seller = new Seller();
            $seller->setUser($user);
            $seller->setName("Boutique $i");
            $seller->setDescription("Description de la boutique $i");
            $seller->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($seller);
            $sellers[] = $seller;
        }

        // Produits et images
        $imgBase = 'https://picsum.photos/seed/';
        $prodCount = 1;
        foreach ($categories as $catIdx => $category) {
            foreach ($sellers as $seller) {
                for ($j = 1; $j <= 2; $j++) {
                    $product = new Product();
                    $product->setName("Produit $prodCount");
                    $product->setDescription("Description du produit $prodCount");
                    $product->setPrice(mt_rand(1000, 10000) / 100);
                    $product->setCreatedAt(new \DateTimeImmutable());
                    $product->setSeller($seller);
                    $product->setCategory($category);
                    $product->setMainImage($imgBase . "main$prodCount" . '/600/400');
                    $manager->persist($product);

                    // 4 petites images
                    for ($k = 1; $k <= 4; $k++) {
                        $img = new ProductImage();
                        $img->setProduct($product);
                        $img->setUrl($imgBase . "prod{$prodCount}_$k" . '/200/200');
                        $img->setType('small');
                        $manager->persist($img);
                    }
                    $prodCount++;
                }
            }
        }

        $manager->flush();
    }
}
