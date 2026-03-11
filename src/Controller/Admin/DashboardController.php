<?php

namespace App\Controller\Admin;

use App\Entity\Food;
use App\Entity\Spice;
use App\Entity\Nutrient;
use App\Entity\Diet\Diet;
use App\Entity\UnitMeasure;
use App\Entity\FoodGroup\FoodGroup;
use App\Entity\FoodGroup\FoodGroupParent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Assiette idéale');
    }

    public function configureMenuItems(): iterable
    {
        return [
            MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home'),
            MenuItem::linkToCrud('Groupes d\'aliments', 'fa fa-foods', FoodGroupParent::class)->setController(FoodGroupParentCrudController::class),
            MenuItem::linkToCrud('Sous Groupes d\'aliments', 'fa fa-foods', FoodGroup::class)->setController(FoodGroupCrudController::class),
            MenuItem::linkToCrud('Aliments', 'fa fa-foods', Food::class),
            MenuItem::linkToCrud('Régimes', 'fa fa-foods', Diet::class),
            MenuItem::linkToCrud('Unités de mesures', 'fa fa-foods', UnitMeasure::class),
            MenuItem::linkToCrud('Epices', 'fa fa-foods', Spice::class),
            MenuItem::linkToCrud('Nutriments', 'fa fa-foods', Nutrient::class),
        ];
    }
}
