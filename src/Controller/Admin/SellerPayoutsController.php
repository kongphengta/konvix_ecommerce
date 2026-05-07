<?php

namespace App\Controller\Admin;

use App\Entity\SellerEarning;
use App\Repository\SellerEarningRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SellerPayoutsController extends AbstractController
{
    #[Route('/admin/seller-payouts', name: 'admin_seller_payouts')]
    public function index(SellerEarningRepository $earningRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Regroupe les gains en attente par vendeur
        $pendingGrouped = $earningRepository->findAllPendingGroupedBySeller();

        return $this->render('admin/seller_payouts.html.twig', [
            'pendingGrouped' => $pendingGrouped,
        ]);
    }

    #[Route('/admin/seller-payouts/{sellerId}/mark-paid', name: 'admin_seller_payouts_mark_paid', methods: ['POST'])]
    public function markPaid(int $sellerId, Request $request, SellerEarningRepository $earningRepository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('payout_' . $sellerId, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_seller_payouts');
        }

        $seller = $em->getRepository(\App\Entity\Seller::class)->find($sellerId);
        if (!$seller) {
            $this->addFlash('danger', 'Vendeur introuvable.');
            return $this->redirectToRoute('admin_seller_payouts');
        }

        $earnings = $earningRepository->findPendingBySeller($seller);
        $now = new \DateTimeImmutable();
        foreach ($earnings as $earning) {
            $earning->setStatus('paid');
            $earning->setPaidAt($now);
        }
        $em->flush();

        $this->addFlash('success', 'Virement marqué comme effectué pour ce vendeur.');
        return $this->redirectToRoute('admin_seller_payouts');
    }
}
