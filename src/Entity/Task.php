<?php

// src/Entity/Task.php
namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Task
{
    #[Assert\NotBlank]
    public string $task;

    #[Assert\NotBlank]
    #[Assert\Type(\DateTimeInterface::class)]
    protected \DateTimeInterface $dueDate;

    public function getTask(): string
    {
        return $this->task;
    }

    public function setTask(string $task): void
    {
        $this->task = $task;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): void
    {
        $this->dueDate = $dueDate;
    }
}