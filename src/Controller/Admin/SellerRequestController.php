<?php

namespace App\Controller\Admin;

use App\Entity\SellerRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SellerRequestController extends AbstractController
{
    #[Route('/admin/seller-requests', name: 'admin_seller_requests')]
    public function index(EntityManagerInterface $em): Response
    {
        $requests = $em->getRepository(SellerRequest::class)->findBy([], ['createdAt' => 'DESC']);
        return $this->render('admin/seller_requests.html.twig', [
            'requests' => $requests,
        ]);
    }

    #[Route('/admin/seller-requests/validate/{id}', name: 'admin_seller_request_validate')]
    public function validate(int $id, EntityManagerInterface $em, MailerInterface $mailer): RedirectResponse
    {
        $request = $em->getRepository(SellerRequest::class)->find($id);
        if ($request && $request->getUser()) {
            $user = $request->getUser();
            // Récupère l'entité Role correspondant au slug 'ROLE_SELLER'
            $roleSeller = $em->getRepository(\App\Entity\Role::class)->findOneBy(['slug' => 'ROLE_SELLER']);
            if ($roleSeller && !$user->getRoles() || !in_array('ROLE_SELLER', $user->getRoles())) {
                $user->addRole($roleSeller);
            }
            $em->persist($user);
            $em->remove($request);
            $em->flush();
            // Envoi de l'email de validation au vendeur
            $email = (new Email())
                ->from('no-reply@konvix-ecommerce.com')
                ->to($user->getEmail())
                ->subject('Votre demande pour devenir vendeur a été acceptée !')
                ->text("Bonjour,\n\nVotre demande pour devenir vendeur sur Konvix Ecommerce a été acceptée. Vous pouvez dès maintenant accéder à votre espace vendeur et commencer à proposer vos produits sur la marketplace.\n\nBienvenue parmi les vendeurs !\n\nL'équipe Konvix Ecommerce");
            $mailer->send($email);
            $this->addFlash('success', 'Le vendeur a été validé, la demande supprimée et un email envoyé.');
        }
        return $this->redirectToRoute('admin_seller_requests');
    }

    #[Route('/admin/seller-requests/refuse/{id}', name: 'admin_seller_request_refuse')]
    public function refuse(int $id, EntityManagerInterface $em): RedirectResponse
    {
        $request = $em->getRepository(SellerRequest::class)->find($id);
        if ($request) {
            $em->remove($request);
            $em->flush();
            $this->addFlash('info', 'La demande a été refusée et supprimée.');
        }
        return $this->redirectToRoute('admin_seller_requests');
    }
}
