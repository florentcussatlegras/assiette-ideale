<?php

namespace App\Controller\Admin;

use App\Entity\FoodGroup\FoodGroup;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class FoodGroupCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FoodGroup::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Nom'),
            TextField::new('semiShortName', 'Nom raccourci'),
            TextField::new('shortName', 'Abréviation'),
            TextField::new('alias', 'Alias'),
            AssociationField::new('forbiddenDiets', 'Régimes alimentaires exclu'),
        ];
    }
    
}
