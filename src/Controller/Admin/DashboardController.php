<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    private AdminUrlGenerator $adminUrlGenerator;

    public function __construct(AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }
    public function index(): Response
    {
        $url = $this->adminUrlGenerator
            ->setController(\App\Controller\Admin\ProductCrudController::class)
            ->generateUrl();
        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Konvix Ecommerce');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Products', 'fa fa-box', \App\Entity\Product::class);
        yield MenuItem::linkToCrud('Product Images', 'fa fa-image', \App\Entity\ProductImage::class);
        yield MenuItem::linkToCrud('Categories', 'fa fa-list', \App\Entity\Category::class);
        yield MenuItem::linkToCrud('Sellers', 'fa fa-user', \App\Entity\Seller::class);
        yield MenuItem::linkToCrud('Reviews', 'fa fa-star', \App\Entity\Review::class);
    }
}
