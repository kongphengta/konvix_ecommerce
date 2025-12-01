<?php
namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DiagnosticController extends AbstractController
{
    #[Route('/diagnostic-image/{id}', name: 'diagnostic_image')]
    public function diagnosticImage(int $id, EntityManagerInterface $em): Response
    {
        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            return new Response('Produit introuvable.');
        }

        $mainImage = $product->getMainImage();
        $imageFolder = $product->getImageFolder();
        $relativePath = '/uploads/product_images/' . $imageFolder . '/' . $mainImage;
        $absolutePath = $this->getParameter('kernel.project_dir') . '/public' . $relativePath;
        $fileExists = file_exists($absolutePath);

        $html = '<h2>Diagnostic image principale</h2>';
        $html .= 'mainImage: ' . htmlspecialchars($mainImage) . '<br>';
        $html .= 'imageFolder: ' . htmlspecialchars($imageFolder) . '<br>';
        $html .= 'Chemin web généré: ' . htmlspecialchars($relativePath) . '<br>';
        $html .= 'Chemin absolu: ' . htmlspecialchars($absolutePath) . '<br>';
        $html .= 'Fichier physique: ' . ($fileExists ? '<span style="color:green">TROUVÉ</span>' : '<span style="color:red">NON TROUVÉ</span>') . '<br>';
        $html .= '<hr><img src="' . $relativePath . '" style="max-width:300px;">';

        return new Response($html);
    }
}
