<?php

namespace App\Twig;

use App\Entity\FoodGroup\FoodGroupParent;
use App\Entity\TypeMeal;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TypeMealExtension extends AbstractExtension
{
	private $em;

	public function __construct(EntityManagerInterface $em)
	{
		$this->em = $em;
	}

	public function getFilters()
	{
		return [
			new TwigFilter('frontName', array($this, 'getFrontName'))
		];
	}

	public function getFrontName($backName)
	{
		return $this->em->getRepository(TypeMeal::class)->findOneByBackName($backName)->getFrontName();
	}
}