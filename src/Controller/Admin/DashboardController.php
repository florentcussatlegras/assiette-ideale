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
        //return parent::index();

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirect('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        
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
        // yield MenuItem::linkToCrud('Sous-régimes', 'fa fa-foods', SubDiet::class);
        yield MenuItem::linkToCrud('Unités de mesures', 'fa fa-foods', UnitMeasure::class);
        yield MenuItem::linkToCrud('Epices', 'fa fa-foods', Spice::class);
        yield MenuItem::linkToCrud('Nutriments', 'fa fa-foods', Nutrient::class);
    }
}
