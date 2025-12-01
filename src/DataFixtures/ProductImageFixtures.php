<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\ProductImage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;

class ProductImageFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $uploadsDir = __DIR__ . '/../../public/uploads';

        // Récupère tous les produits
        $products = $manager->getRepository(Product::class)->findAll();

        foreach ($products as $product) {
            // Adapte ici si le champ n'est pas "getReference"
            $productFolder = $product->getImageFolder(); // Utilise le champ imageFolder

            $productDir = $uploadsDir . '/' . $productFolder;
            if (!is_dir($productDir)) {
                continue;
            }

            $finder = new Finder();
            $finder->files()->in($productDir)->name('/\.(jpg|jpeg|png|avif)$/i')->sortByName();

            $position = 1;
            $mainImageSet = false;
            foreach ($finder as $file) {
                $imagePath = '/uploads/' . $productFolder . '/' . $file->getFilename();
                if (!$mainImageSet) {
                    $product->setMainImage($imagePath);
                    $mainImageSet = true;
                }
                $image = new ProductImage();
                $image->setProduct($product);
                $image->setUrl($imagePath);
                $image->setPosition($position); // Ajoute ce champ dans l'entité si besoin
                $manager->persist($image);
                $position++;
            }
            $manager->persist($product); // Sauvegarde la modification du produit
        }

        $manager->flush();
    }
}
