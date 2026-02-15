<?php

namespace App\Entity\Alert;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "App\Repository\LevelAlertRepository")]
class LevelAlert
{
    // Constantes pour recommandations
    public const RECOMMENDED_WELL_RANGE = 0.1;
    public const RECOMMENDED = 'recommended';
    public const HIGHLY_RECOMMENDED = 'highly_recommended';
    public const NOT_RECOMMENDED = 'not_recommended';
    public const HIGHLY_NOT_RECOMMENDED = 'highly_not_recommended';
    public const STRONGLY_NOT_RECOMMENDED = 'strongly_not_recommended';

    public const MESSAGE_FGP_NOT_RECOMMENDED = 'Vous dépassez %s votre quantité journalière conseillée de %s';
    public const MESSAGE_ENERGY_NOT_RECOMMENDED = 'Vous dépassez %s votre quantité totale de KCal journalière conseillée';
    public const MESSAGE_NUTRIENT_NOT_RECOMMENDED = 'Vous dépassez %s votre quantité journalière conseillée de %s';

    // Constantes pour balance nutritionnelle
    public const BALANCE_WELL_RANGE = 0.1;
    public const BALANCE_WELL = 'balance_well';
    public const BALANCE_LACK = 'balance_lack';
    public const BALANCE_VERY_LACK = 'balance_very_lack';
    public const BALANCE_CRITICAL_LACK = 'balance_critical_lack';
    public const BALANCE_EXCESS = 'balance_excess';
    public const BALANCE_VERY_EXCESS = 'balance_very_excess';
    public const BALANCE_CRITICAL_EXCESS = 'balance_critical_excess';

    // Regroupements par catégorie
    public const LOW_ALERTS = [
        self::BALANCE_LACK,
        self::BALANCE_VERY_LACK,
        self::BALANCE_CRITICAL_LACK,
    ];

    public const HIGH_ALERTS = [
        self::BALANCE_EXCESS,
        self::BALANCE_VERY_EXCESS,
        self::BALANCE_CRITICAL_EXCESS,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string")]
    private string $text;

    #[ORM\Column(type: "string")]
    private string $placeholderText;

    #[ORM\Column(type: "string")]
    private string $color;

    #[ORM\Column(type: "string")]
    private string $code;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $priority = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function getPlaceholderText(): string
    {
        return $this->placeholderText;
    }

    public function setPlaceholderText(string $placeholderText): self
    {
        $this->placeholderText = $placeholderText;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function __toString(): string
    {
        return $this->code;
    }

    public function isExcess(): bool
    {
        return in_array($this->code, [
            self::BALANCE_EXCESS,
            self::BALANCE_VERY_EXCESS,
            self::BALANCE_CRITICAL_EXCESS,
        ], true);
    }

    public function isLack(): bool
    {
        return in_array($this->code, [
            self::BALANCE_LACK,
            self::BALANCE_VERY_LACK,
            self::BALANCE_CRITICAL_LACK,
        ], true);
    }

    public function isWell(): bool
    {
        return $this->code === self::BALANCE_WELL;
    }
}
