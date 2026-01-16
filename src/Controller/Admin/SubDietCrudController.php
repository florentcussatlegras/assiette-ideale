<?php

namespace App\Controller\Admin;

use App\Entity\Diet\SubDiet;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class SubDietCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SubDiet::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Nom'),
            TextField::new('description', 'Description'),
            AssociationField::new('forbiddenFoods', 'Aliments interdits'),
            AssociationField::new('forbiddenFoodGroups', 'Groupe d\'aliments interdits'),
            AssociationField::new('diet', 'Régime parent'),
        ];
    }
    
}
