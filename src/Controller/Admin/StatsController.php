<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\OrderRepository;
use App\Repository\OrderItemRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatsController extends AbstractController
{
    // Affiche les statistiques de ventes dans l'interface d'administration avec possibilité de filtrer par année ou par période
    #[Route('/admin/stats', name: 'admin_stats')]
    public function index(OrderRepository $orderRepository, OrderItemRepository $orderItemRepository, UserRepository $userRepository, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        // Vérifie que l'utilisateur a le rôle ROLE_ADMIN pour accéder à cette page de statistiques
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $year = $request->query->get('year');
        $dateStart = $request->query->get('dateStart');
        $dateEnd = $request->query->get('dateEnd');
        // Récupère les statistiques de ventes en fonction des filtres sélectionnés (année ou période)
        $ca = $orderRepository->getTotalSales($year, $dateStart, $dateEnd);
        $orders = $orderRepository->getOrderCount($year, $dateStart, $dateEnd);
        $avgCart = ($orders > 0) ? ($ca / $orders) : 0;
        $products = $orderRepository->getProductCount($year, $dateStart, $dateEnd);
        $years = $orderRepository->getAvailableYears();
        $monthlyStats = $orderRepository->getMonthlyStats($year, $dateStart, $dateEnd);
        $topProducts = $orderItemRepository->getTopProducts(5, $year, $dateStart, $dateEnd);
        $newClients = $userRepository->getNewClientsByMonth($year, $dateStart, $dateEnd);
        $orderStatusStats = $orderRepository->getOrderStatusStats($year, $dateStart, $dateEnd);
        $deliveredRate = ($orderStatusStats['total'] > 0) ? round($orderStatusStats['delivered'] / $orderStatusStats['total'] * 100, 1) : 0;
        $cancelledRate = ($orderStatusStats['total'] > 0) ? round($orderStatusStats['cancelled'] / $orderStatusStats['total'] * 100, 1) : 0;
        // Rend la vue des statistiques avec les données récupérées pour les afficher dans l'interface d'administration
        return $this->render('admin/stats/index.html.twig', [
            'ca' => $ca,
            'orders' => $orders,
            'products' => $products,
            'monthlyStats' => $monthlyStats,
            'years' => $years,
            'selectedYear' => $year,
            'topProducts' => $topProducts,
            'newClients' => $newClients,
            'avgCart' => $avgCart,
            'orderStatusStats' => $orderStatusStats,
            'deliveredRate' => $deliveredRate,
            'cancelledRate' => $cancelledRate,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
        ]);
    }
    // Exporte les statistiques de ventes en CSV pour une année ou une période donnée, avec un format compatible Excel (BOM UTF-8 et point-virgule comme séparateur)
    #[Route('/admin/stats/export-csv', name: 'admin_stats_export_csv')]
    public function exportCsv(OrderRepository $orderRepository, \Symfony\Component\HttpFoundation\Request $request): \Symfony\Component\HttpFoundation\Response
    {
        // Vérifie que l'utilisateur a le rôle ROLE_ADMIN pour pouvoir exporter les statistiques en CSV
        $year = $request->query->get('year');
        $monthlyStats = $orderRepository->getMonthlyStats($year);
        $filename = 'stats-ventes-mensuelles.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        $handle = fopen('php://temp', 'r+');
        // Ajouter le BOM UTF-8 pour Excel
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Mois', 'Année', 'Nombre de commandes', 'Total (€)'], ';');
        foreach ($monthlyStats as $row) {
            fputcsv($handle, [$row['month'], $row['year'], $row['orders'], number_format($row['total'], 2, ',', ' ')], ';');
        }
        // Rewind le flux et lit son contenu pour le retourner dans la réponse HTTP avec les en-têtes appropriés pour le téléchargement du fichier CSV
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        return new \Symfony\Component\HttpFoundation\Response($content, 200, $headers);
    }
}
