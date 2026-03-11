<?php

namespace App\Controller;

use App\Service\AlertFeature;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * ImcController.php
 * 
 * Contrôleur gérant les fonctionnalités liées à l'IMC (Indice de Masse Corporelle).
 *
 * Il permet notamment d'afficher des explications sur l'IMC de l'utilisateur
 * et de récupérer les alertes associées à sa situation (IMC équilibré, surpoids, etc.).
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale 
*/
#[Route('/imc')]
class ImcController extends AbstractController
{
    /**
     * Affiche la page d'explication de l'IMC de l'utilisateur.
     *
     * @param AlertFeature $alertFeature Service permettant de déterminer les alertes liées à l'IMC
     *
     * @return Response
     */
    #[Route('/explanation', name: 'app_imc_explanation', methods: ['GET'])]
    public function explanation(AlertFeature $alertFeature): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var User $user */
        $user = $this->getUser();

        // Récupération de l'IMC de l'utilisateur
        $imc = $user->getImc();

        // Détermination du niveau d'alerte correspondant à cet IMC
        $balanceImcAlerts = $alertFeature->getImcAlert($imc);

        return $this->render('imc/explanation.html.twig', [
            'imc' => $imc,
            'balanceImcAlerts' => $balanceImcAlerts,
        ]);
    }
}