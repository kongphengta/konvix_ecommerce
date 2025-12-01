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
        $orders = $user->getOrders();
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
