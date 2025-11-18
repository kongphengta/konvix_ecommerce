<?php

namespace App\Controller;

use App\Entity\Seller;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SellerController extends AbstractController
{
    #[Route('/become-seller', name: 'app_become_seller')]
    #[IsGranted('ROLE_USER')]
    public function becomeSeller(EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour devenir vendeur.');
            return $this->redirectToRoute('app_login');
        }
        if (!in_array('ROLE_SELLER', $user->getRoles())) {
            // Ajout du slug dans la méthode getRoles (ManyToMany + slug)
            // Ici, on ne touche pas à la collection ManyToMany, juste au tableau de slugs pour Symfony
            // Créer l'entité Seller si elle n'existe pas déjà
            if ($user->getSeller() === null) {
                $seller = new Seller();
                $seller->setUser($user);
                $entityManager->persist($seller);
            }
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Vous êtes maintenant vendeur !');
        }
        return $this->redirectToRoute('app_home');
    }
}
