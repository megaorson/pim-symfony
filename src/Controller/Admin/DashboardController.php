<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\ProductAttributeRepository;
use App\Repository\ProductRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductAttributeRepository $productAttributeRepository,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'productCount' => $this->productRepository->count([]),
            'attributeCount' => $this->productAttributeRepository->count([]),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle($this->translator->trans('admin.dashboard.title'));
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard($this->translator->trans('admin.menu.dashboard'), 'fa fa-home');
        yield MenuItem::linkTo(
            ProductCrudController::class,
            $this->translator->trans('admin.menu.products'),
            'fa fa-box'
        );
        yield MenuItem::linkTo(
            ProductAttributeCrudController::class,
            $this->translator->trans('admin.menu.product_attributes'),
            'fa fa-box'
        );
    }
}
