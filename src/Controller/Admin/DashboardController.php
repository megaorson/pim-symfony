<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ProductAttributeValueDecimal;
use App\Entity\ProductAttributeValueImage;
use App\Entity\ProductAttributeValueInt;
use App\Entity\ProductAttributeValueText;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Product;
use App\Entity\ProductAttribute;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function index(): Response
    {
        $productCount = $this->em->getRepository(Product::class)->count([]);
        $attributeCount = $this->em->getRepository(ProductAttribute::class)->count([]);

        $textCount = $this->em->getRepository(ProductAttributeValueText::class)->count([]);
        $decimalCount = $this->em->getRepository(ProductAttributeValueDecimal::class)->count([]);
        $intCount = $this->em->getRepository(ProductAttributeValueInt::class)->count([]);
        $imageCount = $this->em->getRepository(ProductAttributeValueImage::class)->count([]);

        $totalValues = $textCount + $decimalCount + $intCount + $imageCount;

        $avgValuesPerProduct = $productCount > 0
            ? round($totalValues / $productCount, 1)
            : 0;

        return $this->render('admin/dashboard.html.twig', [
            'productCount' => $productCount,
            'attributeCount' => $attributeCount,
            'totalValues' => $totalValues,
            'avgValuesPerProduct' => $avgValuesPerProduct,
            'textCount' => $textCount,
            'decimalCount' => $decimalCount,
            'intCount' => $intCount,
            'imageCount' => $imageCount,
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
