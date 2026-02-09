<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\WeightLog;
use App\Repository\WeightLogRepository;
use App\Service\AlertFeature;
use App\Service\EnergyHandler;
use Doctrine\ORM\EntityManagerInterface;

class EnergyAdjustmentManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private AlertFeature $alertFeature,
        private EnergyHandler $energyHandler
    ) {}

    public function logNewWeight(User $user): User
    {
        $log = new WeightLog();
        $log->setUser($user);
        $log->setWeight($user->getWeight());

        $this->em->persist($log);

        // 4️⃣ Si un régime est actif, ajuster le calorieAdjustmentPercent
        if ($user->isWeightGoalActive()) {
            $user = $this->recalculateDietIfNeeded($user);
        }

        return $user;
    }

    public function needsWeeklyUpdate(User $user): bool
    {
        if (!$user->isWeightGoalActive()) {
            return false;
        }

        $last = $user->getLastWeightUpdateAt();

        if (!$last) {
            return true;
        }

        return $last->diff(new \DateTime())->days >= 7;
    }

    private function recalculateDietIfNeeded(User $user): User
    {
        $imcAlert = $this->alertFeature->getImcAlert($user->getImc());
        $newPercent = $this->alertFeature->getCalorieAdjustmentPercent($imcAlert);

        if ($user->getCalorieAdjustmentPercent() !== $newPercent) {
            $user->setCalorieAdjustmentPercent($newPercent);
        }

        $energy = $this->energyHandler->evaluateEnergy();

        $adjusted = $energy * (1 + $newPercent / 100);

        $user->setEnergy((int) $adjusted);

        return $user;
    }
}
