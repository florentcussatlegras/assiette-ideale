<?php

namespace App\DataCollector;

use Throwable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;

class SessionDishDataCollector extends AbstractDataCollector
{
    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $session = $request->getSession();

        if ($session->has('recipe_foods') && !empty($session->get('recipe_foods'))) {
            $this->data = [
                'session_recipe_foods' => $session->get('recipe_foods'),
                'count_foods' => count($session->get('recipe_foods'), COUNT_RECURSIVE)
            ];
        }

        if ($session->has('recipe_picture') && !empty($session->get('recipe_picture'))) {
            $this->data = [
                'session_recipe_picture' => $session->get('recipe_picture'),
            ];
        }
    }

    public function getListfoods(): ?array
    {
        return $this->data['session_recipe_foods'] ?? [];
    }

    public function getPicture(): ?array
    {
        return $this->data['session_recipe_picture'] ?? null;
    }

    public function getCountfoods(): ?int
    {
        return $this->data['count_foods'];
    }

    public static function getTemplate(): ?string
    {
        return 'data_collector/session_recipe_foods.html.twig';
    }
}