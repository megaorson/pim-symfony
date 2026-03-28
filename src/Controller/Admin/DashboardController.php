<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\ProductRepository;
use App\Repository\ProductAttributeRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private ProductRepository $productRepository,
        private ProductAttributeRepository $productAttributeRepository
    ) {}

    public function index(): Response
    {
        $productCount = $this->productRepository->count([]);
        $attributeCount = $this->productAttributeRepository->count([]);

        return $this->render('admin/dashboard.html.twig', [
            'productCount' => $productCount,
            'attributeCount' => $attributeCount,
        ]);
    }

    public function configureDashboard()
    : Dashboard
    {
        return Dashboard::new()
            ->setTitle('Pim');
    }

    public function configureMenuItems()
    : iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(
            ProductCrudController::class,
            'Products',
            'fa fa-box'
        );
        yield MenuItem::linkTo(
            ProductAttributeCrudController::class,
            'Product Attribute',
            'fa fa-box'
        );
    }
}
