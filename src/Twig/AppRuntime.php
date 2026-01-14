<?php

namespace App\Twig;

use App\Service\AlertFeature;
use App\Repository\FoodGroupParentRepository;
use App\Repository\NutrientRepository;
use Twig\Extension\RuntimeExtensionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AppRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private FoodGroupParentRepository $foodGroupParentRepository,
        private NutrientRepository $nutrientRepository,
    )
    {}

    public function getFoodGroupParentsId($dish)
	{
		foreach($dish->getDishFoodGroupParents()->toArray() as $fgp)
		{
			$results[] = $fgp->getId();
		}

		return $results;
	}

	public function getFoodGroupParentByAlias($alias)
	{
		return $this->foodGroupParentRepository->findOneByAlias($alias);
	}

    public function getNutrientByCode($code)
	{
		return $this->nutrientRepository->findOneByCode($code);
	}

    // public function getMessageBalanceSheetAlert($alert, $subject, $subSubject = null)
    // {
    //     switch ($subject) 
    //     {
    //         case 'energy':
    //             return $this->formatMessageEnergy($alert);
    //             break;
    //         case 'food_group_parent':
    //             return $this->formatMessageFgp($alert, $subSubject);
    //             break;
    //         case 'nutrient':
    //             return $this->formatMessageNutrient($alert, $subSubject);
    //             break;
    //     }   
    // }

    // public function formatMessageEnergy($alert)
    // {
    //     switch ($alert)
    //     {
    //         case AlertFeature::BALANCE_EXCESS:
    //             return 'Elevée';
    //             break;
    //         case AlertFeature::BALANCE_LACK:
    //             return 'Faible';
    //             break;
    //         case AlertFeature::BALANCE_WELL:
    //             return 'Bonne';
    //             break;
    //     }
    // }

    // public function formatMessageFgp($alert, $subSubjectAlias)
    // {
    //     $fgp = $this->foodGroupParentRepository->findOneByAlias($subSubjectAlias);
    //     if(null === $fgp) {
    //         throw new NotFoundHttpException(sprintf('Le groupe parent d\'aliment %s n\'existe pas', $subSubjectAlias));
    //     }
 
    //     switch ($alert)
    //     {
    //         case AlertFeature::BALANCE_EXCESS:
    //             return 'Elevée';
    //         break;
    //         case AlertFeature::BALANCE_LACK:
    //             return 'Faible';
    //         break;
    //         case AlertFeature::BALANCE_WELL:
    //             return 'Bonne';
    //         break;
    //     }
    // }

    // public function formatMessageNutrient($alert, $subSubject)
    // {
    //     switch ($alert)
    //     {
    //         case AlertFeature::BALANCE_EXCESS:
    //             return 'Elevée';
    //         break;
    //         case AlertFeature::BALANCE_LACK:
    //             return 'Faible';
    //         break;
    //         case AlertFeature::BALANCE_WELL:
    //             return 'Bonne';
    //         break;
    //     }
    // }
}