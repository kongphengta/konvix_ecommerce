<?php
// Diagnostic pour l'image principale d'un produit
use App\Kernel;
use App\Entity\Product;

require_once __DIR__ . '/../vendor/autoload.php';
$kernel = new Kernel('dev', true);
$kernel->boot();
$entityManager = $kernel->getContainer()->get('doctrine')->getManager();

$productId = 1015; // Remplace par l'ID du produit à tester
$product = $entityManager->getRepository(Product::class)->find($productId);

if (!$product) {
    echo "Produit introuvable.<br>";
    exit;
}

// 2. Affichage des propriétés
echo "mainImage: " . $product->getMainImage() . "<br>";
echo "imageFolder: " . $product->getImageFolder() . "<br>";

// 3. Construction du chemin
$relativePath = '/uploads/product_images/' . $product->getImageFolder() . '/' . $product->getMainImage();
echo "Chemin web généré: " . $relativePath . "<br>";

// 4. Vérification du fichier physique
$absolutePath = __DIR__ . $relativePath;
if (file_exists($absolutePath)) {
    echo "Fichier trouvé: " . $absolutePath . "<br>";
} else {
    echo "Fichier NON trouvé: " . $absolutePath . "<br>";
}

// 5. Test d'accès web
echo '<img src="' . $relativePath . '" style="max-width:300px;">';
