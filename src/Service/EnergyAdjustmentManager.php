<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\WeightLog;
use App\Repository\WeightLogRepository;
use App\Service\AlertFeature;
use App\Service\EnergyHandler;
use Doctrine\ORM\EntityManagerInterface;

/**
 * EnergyAdjustmentManager.php
 *
 * Service chargé de gérer l'ajustement énergétique d'un utilisateur
 * en fonction de l'évolution de son poids et de son objectif de poids.
 *
 * Il permet :
 * - d'enregistrer un historique du poids (WeightLog)
 * - de recalculer automatiquement l'ajustement calorique
 *   si un objectif de poids est actif
 * - de déterminer si une mise à jour hebdomadaire est nécessaire
 *
 * L'ajustement énergétique repose sur :
 * - l'IMC de l'utilisateur
 * - les alertes nutritionnelles (AlertFeature)
 * - l'évaluation énergétique (EnergyHandler)
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class EnergyAdjustmentManager
{
    /**
     * Injection des dépendances via constructeur
     */
    public function __construct(
        private EntityManagerInterface $em, // Gestion des entités Doctrine
        private AlertFeature $alertFeature, // Gestion des alertes nutritionnelles (IMC, ajustements)
        private EnergyHandler $energyHandler // Calcul des besoins énergétiques de base
    ) {}

    /**
     * Enregistre un nouveau poids dans l'historique utilisateur.
     *
     * Si l'utilisateur suit un objectif de poids (perte ou prise),
     * le système recalculera automatiquement l'ajustement calorique.
     *
     * @param User $user
     * @return User
     */
    public function logNewWeight(User $user): User
    {
        // Création d'une entrée d'historique du poids
        $log = new WeightLog();
        $log->setUser($user);
        $log->setWeight($user->getWeight());

        $this->em->persist($log);

        // Si un objectif de poids est actif, recalcul du régime
        if ($user->isWeightGoalActive()) {
            $user = $this->recalculateDietIfNeeded($user);
        }

        return $user;
    }

    /**
     * Vérifie si une mise à jour hebdomadaire du régime est nécessaire.
     *
     * Le recalcul est effectué si :
     * - un objectif de poids est actif
     * - la dernière mise à jour date de plus de 7 jours
     *
     * @param User $user
     * @return bool
     */
    public function needsWeeklyUpdate(User $user): bool
    {
        if (!$user->isWeightGoalActive()) {
            return false;
        }

        $last = $user->getLastWeightUpdateAt();

        // Si aucune mise à jour n'existe, il faut recalculer
        if (!$last) {
            return true;
        }

        // Vérifie si 7 jours se sont écoulés
        return $last->diff(new \DateTime())->days >= 7;
    }

    /**
     * Recalcule l'ajustement énergétique du régime si nécessaire.
     *
     * Le calcul repose sur :
     * - l'IMC actuel de l'utilisateur
     * - le niveau d'alerte associé
     * - le pourcentage d'ajustement calorique correspondant
     *
     * L'énergie finale est ensuite recalculée.
     *
     * @param User $user
     * @return User
     */
    private function recalculateDietIfNeeded(User $user): User
    {
        // Détermination du niveau d'alerte IMC
        $imcAlert = $this->alertFeature->getImcAlert($user->getImc());

        // Récupération du pourcentage d'ajustement calorique
        $newPercent = $this->alertFeature->getCalorieAdjustmentPercent($imcAlert);

        // Mise à jour si le pourcentage a changé
        if ($user->getCalorieAdjustmentPercent() !== $newPercent) {
            $user->setCalorieAdjustmentPercent($newPercent);
        }

        // Calcul des besoins énergétiques de base
        $energy = $this->energyHandler->evaluateEnergy();

        // Application de l'ajustement calorique
        $adjusted = $energy * (1 + $newPercent / 100);

        // Mise à jour de l'énergie utilisateur
        $user->setEnergy((int) $adjusted);

        return $user;
    }
}