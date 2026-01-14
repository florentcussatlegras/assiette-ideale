<?php

namespace App\Entity\Alert;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LevelAlertRepository")
 */
class LevelAlert
{
    const RECOMMENDED_WELL_RANGE = 0.1;
    const RECOMMENDED = 'recommended';
    const HIGHLY_RECOMMENDED = 'highly_recommended';
    const NOT_RECOMMENDED = 'not_recommended';
    const HIGHLY_NOT_RECOMMENDED = 'highly_not_recommended';
    const STRONGLY_NOT_RECOMMENDED = 'strongly_not_recommended';

    // const ALREADY_NOT_RECOMMENDED = 'already_not_recommended';
    // const NOT_ALREADY_NOT_RECOMMENDED = 'not_already_not_recommended';

    // const MESSAGE_FGP_ALREADY_NOT_RECOMMENDED = 'Vous avez dépassé les quantités conseillées en %s';
    // const MESSAGE_FGP_NOT_RECOMMENDED = 'Les quantités conseillées en %s sont %s dépassées';
    const MESSAGE_FGP_NOT_RECOMMENDED = 'Vous dépassez %s votre quantité journalière conseillée de %s';
    const MESSAGE_ENERGY_NOT_RECOMMENDED = 'Vous dépassez %s votre quantité totale de KCal journalière conseillée';
    const MESSAGE_NUTRIENT_NOT_RECOMMENDED = 'Vous dépassez %s votre quantité journalière conseillée de %s';

    const BALANCE_WELL_RANGE = 0.1; // 20% e.g. energy moyenne par jour est correct si entre +-20% energie journalière recommendé (même chose foodgroup parent et nutriments);
	const BALANCE_WELL = 'balance_well';
	const BALANCE_LACK = 'balance_lack';
	const BALANCE_VERY_LACK = 'balance_very_lack';
	const BALANCE_CRITICAL_LACK = 'balance_critical_lack';
	const BALANCE_EXCESS = 'balance_excess';
	const BALANCE_VERY_EXCESS = 'balance_very_excess';
	const BALANCE_CRITICAL_EXCESS = 'balance_critical_excess';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $text;

    /**
     * @ORM\Column(type="string")
     */
    private $placeholderText;

    /**
     * @ORM\Column(type="string")
     */
    private $color;

    /**
     * @ORM\Column(type="string")
     */
    private $code;

    /**
     * @ORM\Column(type="integer", nullable="true")
     */
    private $priority;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getCode(): ?string
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

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function __toString()
    {
        return $this->getText();
    }

    public function getPlaceholderText(): ?string
    {
        return $this->placeholderText;
    }

    public function setPlaceholderText(string $placeholderText): static
    {
        $this->placeholderText = $placeholderText;

        return $this;
    }
}