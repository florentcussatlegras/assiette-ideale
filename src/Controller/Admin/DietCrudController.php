<?php

namespace App\Controller\Admin;

use App\Entity\Diet\Diet;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class DietCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Diet::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Nom'),
            TextField::new('description', 'Description'),
            AssociationField::new('forbiddenFoodGroups', 'Groupe d\'aliments interdits'),
            AssociationField::new('authorizedFoods', 'Aliments autorisés (seront exclus des groupes interdits)'),
            AssociationField::new('forbiddenFoods', 'Aliments interdits'),
        ];
    }
    
}
