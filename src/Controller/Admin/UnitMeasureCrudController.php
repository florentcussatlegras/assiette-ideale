<?php

namespace App\Controller\Admin;

use App\Entity\UnitMeasure;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class UnitMeasureCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UnitMeasure::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            Field::new('name', 'Nom'),
            Field::new('alias', 'Abréviation'),
            Field::new('isUnit', 'Utilisé pour des unités'),
            Field::new('gramRatio', 'Equivalent en gramme')
        ];
    }
    
}
