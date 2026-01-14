<?php

namespace App\Twig;

use App\Twig\AppRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class NutrientExtension extends AbstractExtension
{
	public function getFilters()
	{
		return [
			new TwigFilter('nutrient', [AppRuntime::class, 'getNutrientByCode'])
		];
	}	
}