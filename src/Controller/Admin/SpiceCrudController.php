<?php

namespace App\Controller\Admin;

use App\Entity\Spice;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class SpiceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Spice::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            Field::new('name', 'Nom'),
        ];
    }
    
}
