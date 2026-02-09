<?php

namespace App\Controller\Admin;

use App\Entity\Food;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class FoodCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Food::class;
    }
 
    public function configureFields(string $pageName): iterable
    {
        return [
            Field::new('name', 'Nom'),
            Field::new('isSubFoodGroup', 'Utilisé comme sous-groupe'),
            AssociationField::new('subFoodGroup', 'Appartient au sous-groupe d\'aliments'),
            AssociationField::new('foodGroup', 'Appartient au groupe'),
            AssociationField::new('unitMeasures', 'Unités de mesures'),
            AssociationField::new('forbiddenDiets', 'Régimes alimentaires exclus'),
            Field::new('medianWeight', 'Poids moyen (en grammes)'),
            Field::new('haveGluten', 'Contient du gluten'),
            Field::new('haveLactose', 'Contient du lactose'),
            Field::new('notConsumableRaw', 'Aliment brut'),
            Field::new('canBeAPart', 'Peut être partitionné'),
            Field::new('energy', 'Energie en kcal (pour 100g ou 100ml'),
            Field::new('picture', 'Image')
        ];
    }
    
}
