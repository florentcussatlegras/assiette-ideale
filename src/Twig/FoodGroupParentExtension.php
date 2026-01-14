<?php

namespace App\Twig;

use App\Twig\AppRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FoodGroupParentExtension extends AbstractExtension
{
	public function getFilters()
	{
		return [
			new TwigFilter('foodGroupParentsId', [AppRuntime::class, 'getFoodGroupParentsId']),
			new TwigFilter('foodGroupParent', [AppRuntime::class, 'getFoodGroupParentByAlias'])
		];
	}	
}