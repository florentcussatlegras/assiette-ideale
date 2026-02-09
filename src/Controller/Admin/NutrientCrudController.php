<?php

namespace App\Controller\Admin;

use App\Entity\Nutrient;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class NutrientCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Nutrient::class;
    }
  
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Nom'),
            TextField::new('description', 'Description'),
        ];
    }
    
}
