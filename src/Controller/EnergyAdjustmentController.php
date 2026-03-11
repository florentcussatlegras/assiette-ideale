<?php

namespace App\Controller;

use App\Entity\Alert\LevelAlert;
use App\Repository\ImcMessageRepository;
use App\Service\AlertFeature;
use App\Service\EnergyHandler;
use App\Service\ProfileHandler;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * EnergyAdjustmentController.php
 * 
 * Contrôleur gérant le suivi et l'ajustement énergétique des utilisateurs
 * selon leur IMC et leur objectif de poids.
 * 
 * Fonctionnalités principales :
 * - Affichage des messages et propositions d'ajustement énergétique
 * - Acceptation d'un ajustement calorique recommandé
 * - Explications détaillées des ajustements
 * - Arrêt du suivi énergétique
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 */
#[Route('/energy-adjustment', name: 'app_energy_adjustment')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class EnergyAdjustmentController extends AbstractController
{
    /**
     * Affiche le message personnalisé selon l'IMC et l'état du suivi de poids
     * 
     * @param AlertFeature $alertFeature Service pour gérer les alertes nutritionnelles
     * @param ImcMessageRepository $imcMessageRepository Repository pour récupérer les messages IMC
     * 
     * @return Response
     */
    #[Route('/message', name: '_message', methods: ['GET'])]
    public function message(AlertFeature $alertFeature, ImcMessageRepository $imcMessageRepository): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Initialise les variables qui détermineront le message à afficher et si une proposition de suivi doit être faite
        $showWeightGoalProposal = false; // Booléen pour savoir si on propose à l'utilisateur d'ajuster ses apports
        $weightGoalMessage = null;       // Message textuel qui sera affiché à l'utilisateur

        // Récupère l'alerte IMC correspondant à l'IMC actuel de l'utilisateur
        // L'alerte détermine si son apport calorique est équilibré, insuffisant ou excessif
        $imcAlert = $alertFeature->getImcAlert($user->getImc());

        // Récupère le message associé à ce niveau d'alerte depuis la base (repository IMC)
        $message = $imcMessageRepository->findByAlertCode($imcAlert->getCode())->getMessage();

        // Si l'IMC est équilibré
        if ($imcAlert->getCode() === LevelAlert::BALANCE_WELL) {
            // Si le suivi du poids n'est pas actif, on informe simplement l'utilisateur
            if (!$user->isWeightGoalActive()) {
                $weightGoalMessage = "Vous avez un apport calorique équilibré";
            } else {
                // Sinon, on rappelle que le suivi calorique est actif pour maintenir l'équilibre
                $weightGoalMessage = "Nous suivons votre apport calorique pour maintenir votre équilibre.";
            }
        } else {
            // Si l'IMC n'est pas équilibré
            if (!$user->isWeightGoalActive()) {
                // Proposer à l'utilisateur d'ajuster ses apports caloriques
                $showWeightGoalProposal = true;
                $weightGoalMessage = "Voulez-vous ajuster vos apports caloriques ?";
            } else {
                // Si le suivi calorique est déjà actif, informer que les besoins sont ajustés
                $weightGoalMessage = "Vos besoins énergétiques sont réajustés pour favoriser un retour vers un IMC équilibré.";
            }
        }

        // Retourne la vue avec toutes les informations nécessaires pour l'affichage
        return $this->render('energy_adjustment/message.html.twig', [
            'showWeightGoalProposal' => $showWeightGoalProposal, // Indique si un bouton de proposition doit être affiché
            'weightGoalMessage' => $weightGoalMessage,           // Message personnalisé selon l'état de l'utilisateur
            'isWeightGoalActive' => $user->isWeightGoalActive(), // Booléen pour savoir si le suivi est actif
            'imcAlert' => $imcAlert,                             // Objet représentant l'alerte IMC
            'message' => $message,                               // Message général lié à l'alerte IMC
        ]);
    }

    /**
     * Active l'ajustement calorique recommandé pour l'utilisateur
     * et recalcule automatiquement son énergie
     * 
     * @param EntityManagerInterface $em
     * @param AlertFeature $alertFeature
     * @param EnergyHandler $energyHandler
     * @param ProfileHandler $profileHandler
     * 
     * @return Response
     */
    #[Route('/accept', name: '_accept', methods: ['GET', 'POST'])]
    public function accept(
        EntityManagerInterface $em,
        AlertFeature $alertFeature,
        EnergyHandler $energyHandler,
        ProfileHandler $profileHandler
    ): Response {

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Récupère l'alerte IMC correspondant à l'IMC actuel de l'utilisateur
        // Cela permet de connaître le niveau d'alerte pour ajuster les calories
        $imcAlert = $alertFeature->getImcAlert($user->getImc());

        // Calcule le pourcentage d'ajustement calorique recommandé en fonction de l'alerte IMC
        $adjustmentPercent = $alertFeature->getCalorieAdjustmentPercent($imcAlert);

        // Stocke le pourcentage d'ajustement sur l'utilisateur
        $user->setCalorieAdjustmentPercent($adjustmentPercent);

        // Active le suivi des objectifs de poids pour cet utilisateur
        $user->setIsWeightGoalActive(true);

        // Forcer le recalcul automatique de l'énergie selon les besoins de l'utilisateur
        $user->setAutomaticCalculateEnergy(true);

        // Recalcul immédiat de l'énergie en utilisant la méthode evaluateEnergy()
        // qui estime les besoins énergétiques de l'utilisateur selon ses données (poids, taille, activité, etc.)
        try {
            $energyEstimate = $energyHandler->evaluateEnergy();

            // Ajuste l'énergie estimée selon le pourcentage calculé
            // Exemple : adjustmentPercent = 10 signifie que l'énergie ajustée = énergie estimée * 1.10
            $energyAdjusted = $energyEstimate * (1 + $adjustmentPercent / 100);

            // Stocke l'énergie ajustée pour le suivi du régime
            $user->setEnergy($energyAdjusted);

            // Conserve également l'énergie estimée "non ajustée" pour référence ou calculs futurs
            $user->setEnergyCalculate($energyEstimate);

            // Recalcule le profil nutritionnel de l'utilisateur en fonction de l'énergie ajustée
            $profileHandler->recalcUserProfile();
        } catch (\App\Exception\MissingElementForEnergyEstimationException $e) {
            // Gère le cas où il manque des informations pour calculer l'énergie
            // Retourne une réponse JSON d'erreur
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }

        // Persiste les modifications de l'utilisateur en base de données
        $em->persist($user);
        $em->flush();

        // Message flash pour informer l'utilisateur que son besoin énergétique a été réévalué
        $this->addFlash(
            'info',
            'Votre besoin énergétique et vos recommandations nutritionnelles ont été réévalués afin de corriger votre poids et votre IMC'
        );

        // Redirection vers le tableau de bord après ajustement énergétique
        return $this->redirectToRoute('app_dashboard_index');
    }

    /**
     * Fournit une explication détaillée de l'ajustement énergétique
     * et de l'IMC idéal
     * 
     * @param AlertFeature $alertFeature
     * @param ImcMessageRepository $imcMessageRepository
     * 
     * @return Response
     */
    #[Route('/explanation', name: '_explanation', methods: ['GET'])]
    public function explication(AlertFeature $alertFeature, ImcMessageRepository $imcMessageRepository): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Récupère l'alerte IMC correspondant à l'IMC actuel de l'utilisateur
        // L'alerte contient des informations comme le code et le niveau d'alerte (ex. insuffisant, équilibré, excessif)
        $imcAlert = $alertFeature->getImcAlert($user->getImc());

        // Récupère le message associé au code de l'alerte IMC
        // Ce message explique la situation de l'utilisateur par rapport à son IMC
        $imcMessage = $imcMessageRepository->findByAlertCode($imcAlert->getCode());

        // Conversion de la taille de l'utilisateur en mètres (la taille est stockée en cm)
        $height = $user->getHeight() / 100;

        // Récupération de l'IMC cible idéal pour l'utilisateur
        $idealImc = $user->getIdealImc();

        // Calcul du poids cible à atteindre pour cet IMC idéal
        // Formule : poids = IMC * taille^2
        // On arrondit le résultat à une décimale
        $weightTarget = round($height * $height * $idealImc, 1);

        // Retourne la vue avec toutes les informations nécessaires pour l'affichage
        return $this->render('energy_adjustment/explanation.html.twig', [
            'imcMessage'     => $imcMessage, // le message explicatif associé à l'IMC actuel
            'imcAlert'       => $imcAlert, // l'objet alerte IMC avec le code et le niveau
            'energyBase'     => $user->getEnergyCalculate(), // l'énergie de référence calculée automatiquement pour l'utilisateur
            'energyAdjusted' => $user->getEnergy(), // l'énergie ajustée selon les objectifs de poids
            'weightTarget'   => $weightTarget, // le poids cible correspondant à l'IMC idéal
        ]);
    }

    /**
     * Arrête le suivi calorique de l'utilisateur
     * et remet à jour son besoin énergétique de base
     * 
     * @param EntityManagerInterface $em
     * @param EnergyHandler $energyHandler
     * @param ProfileHandler $profileHandler
     * 
     * @return Response
     */
    #[Route('/stop', name: '_stop', methods: ['POST', 'GET'])]
    public function stop(EntityManagerInterface $em, EnergyHandler $energyHandler, ProfileHandler $profileHandler)
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Désactivation du suivi de l'objectif de poids
        $user->setIsWeightGoalActive(false);

        // Réinitialisation du pourcentage d'ajustement calorique
        $user->setCalorieAdjustmentPercent(0);

        // Repasser en calcul automatique de l'énergie
        // Cela permet de recalculer l'énergie de base selon les données normales de l'utilisateur
        $user->setAutomaticCalculateEnergy(true);

        try {
            // Recalcul de l’énergie "normale" (non ajustée)
            // La méthode evaluateEnergy() calcule les besoins énergétiques de base selon le poids,
            // la taille, l'âge, le sexe et le niveau d'activité de l'utilisateur
            $baseEnergy = $energyHandler->evaluateEnergy();
            $user->setEnergy((int) $baseEnergy);

            // Recalcul du profil nutritionnel et énergétique de l'utilisateur
            // Cela met à jour toutes les recommandations et valeurs dépendantes de l'énergie
            $profileHandler->recalcUserProfile();
        } catch (\Exception $e) {
            // Gestion d'erreur si le recalcul échoue (ex. données manquantes ou incohérentes)
            $this->addFlash('error', 'Impossible de recalculer votre besoin énergétique.');
        }

        // Persistance des modifications dans la base de données
        $em->persist($user);
        $em->flush();

        // Message de confirmation pour l'utilisateur
        $this->addFlash(
            'success',
            'Le suivi régime a été arrêté. Votre besoin énergétique et vos recommandations nutritionnelles ont été recalculés.'
        );

        // Redirection vers le tableau de bord après la mise à jour
        return $this->redirectToRoute('app_dashboard_index');
    }
}
