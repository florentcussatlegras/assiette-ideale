<?php

namespace App\Twig;

use App\Entity\FoodGroup\FoodGroupParent;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DishExtension extends AbstractExtension
{
	private $em;

	public function __construct(EntityManagerInterface $em)
	{
		$this->em = $em;
	}

	public function getFunctions()
	{
		return [
			new TwigFunction('showKeyword', array($this, 'setShowKeyword'))
		];
	}

	public function setShowKeyword($name, $keyword)
	{
		if(false !== $start = stripos($name, trim($keyword)))
		{
			$search = substr($name, $start, strlen($keyword));

			return str_replace ($search, '<span class="text-dark-blue font-weight-800">' . $search .'</span>', $name);
		}

		return $name;
	}
}