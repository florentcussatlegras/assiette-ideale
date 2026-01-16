<?php

namespace App\Controller\Admin;

use App\Entity\Food;
use App\Entity\Spice;
use App\Entity\Nutrient;
use App\Entity\Diet\Diet;
use App\Entity\UnitMeasure;
use App\Entity\Diet\SubDiet;
use App\Entity\FoodGroup\FoodGroup;
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
            ->setTitle('Liveforeat');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Groupes d\'aliments', 'fa fa-foods', FoodGroup::class);
        yield MenuItem::linkToCrud('Aliments', 'fa fa-foods', Food::class);
        yield MenuItem::linkToCrud('Régimes', 'fa fa-foods', Diet::class);
        yield MenuItem::linkToCrud('Unités de mesures', 'fa fa-foods', UnitMeasure::class);
        yield MenuItem::linkToCrud('Epices', 'fa fa-foods', Spice::class);
        yield MenuItem::linkToCrud('Nutriments', 'fa fa-foods', Nutrient::class);
    }
}
