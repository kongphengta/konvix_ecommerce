<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ChangePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile/become-seller', name: 'profile_become_seller')]
    public function becomeSeller(Request $request,EntityManagerInterface $entityManager ): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour effectuer cette action.');
            return $this->redirectToRoute('app_login');
        }
        $form = $this->createForm(\App\Form\BecomeSellerFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $sellerRequest = new \App\Entity\SellerRequest();
            $sellerRequest->setShopName($data['shopName']);
            $sellerRequest->setShopDescription($data['shopDescription']);
            $sellerRequest->setContactEmail($data['contactEmail']);
            $sellerRequest->setContactPhone($data['contactPhone']);
            $sellerRequest->setUser($user);
            $entityManager->persist($sellerRequest);
            $entityManager->flush();
            $this->addFlash('success', 'Votre demande pour devenir vendeur a été enregistrée. Un administrateur va la traiter.');
            return $this->redirectToRoute('app_profile');
        }
        return $this->render('profile/become_seller.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/order/{id}/invoice', name: 'app_order_invoice')]
    public function invoice(int $id, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour accéder à vos factures.');
            return $this->redirectToRoute('app_login');
        }
        $order = $entityManager->getRepository(\App\Entity\Order::class)->find($id);
        if (!$order || $order->getUser() !== $user) {
            throw $this->createNotFoundException('Commande introuvable ou accès refusé.');
        }
        // Génération du PDF (exemple simple avec HTML)
        $html = $this->renderView('profile/invoice_pdf.html.twig', [
            'order' => $order,
            'user' => $user,
        ]);
        // Utilisation de Dompdf
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfOutput = $dompdf->output();
        return new Response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="facture_' . $order->getId() . '.pdf"',
        ]);
    }

    #[Route('/profile/orders', name: 'app_profile_orders')]
    public function orders(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour accéder à vos commandes.');
            return $this->redirectToRoute('app_login');
        }
        // Récupérer les 5 dernières commandes, la plus récente en premier
        $orders = $entityManager->getRepository(\App\Entity\Order::class)
            ->findBy(['user' => $user], ['createdAt' => 'DESC'], 5);
        return $this->render('profile/orders.html.twig', [
            'orders' => $orders,
            'user' => $user,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour modifier vos informations.');
            return $this->redirectToRoute('app_login');
        }
        $form = $this->createForm(\App\Form\ProfileEditFormType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour.');
            return $this->redirectToRoute('app_profile');
        }
        return $this->render('profile/edit.html.twig', [
            'editForm' => $form->createView(),
        ]);
    }
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour accéder à votre profil.');
            return $this->redirectToRoute('app_login');
        }
        // Message flash de bienvenue uniquement si l'utilisateur arrive sur la page (GET)
        $session = $request->getSession();
        $flashes = $session->getFlashBag()->peek('success');
        if ($request->isMethod('GET') && empty($flashes)) {
            $this->addFlash('success', 'Bienvenue sur votre espace client.');
        }
        // Récupérer les 5 dernières commandes, la plus récente en premier pour l'espace client
        $orders = $entityManager->getRepository(\App\Entity\Order::class)
            ->findBy(['user' => $user], ['createdAt' => 'DESC'], 5);
        // Gestion du formulaire de changement de mot de passe
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $form->get('currentPassword')->addError(new \Symfony\Component\Form\FormError('Mot de passe actuel incorrect.'));
            } elseif ($newPassword !== $confirmPassword) {
                $form->get('confirmPassword')->addError(new \Symfony\Component\Form\FormError('Les mots de passe ne correspondent pas.'));
            } else {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $entityManager->flush();
                $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');
                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'orders' => $orders,
            'changePasswordForm' => $form->createView(),
        ]);
    }
}
