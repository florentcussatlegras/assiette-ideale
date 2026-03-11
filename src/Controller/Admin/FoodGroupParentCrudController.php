<?php

namespace App\Controller\Admin;

use App\Entity\FoodGroup\FoodGroupParent;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

class FoodGroupParentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FoodGroupParent::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Nom'),
            TextField::new('semiShortName', 'Nom raccourci'),
            TextField::new('shortName', 'Abréviation'),
            TextField::new('alias', 'Alias'),
            TextField::new('color', 'Couleur primaire'),
            TextField::new('degradedColor', 'Couleur secondaire'),
            
            // Champs textarea / WYSIWYG
            TextEditorField::new('content', 'Contenu'),
            TextEditorField::new('funFact', 'Fait marrant'),
        ];
    }
}
