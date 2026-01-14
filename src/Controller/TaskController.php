<?php
// src/Controller/TaskController.php
namespace App\Controller;

use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends AbstractController
{
    #[Route('/task', name: 'app_task')]
    public function new(Request $request): Response
    {
        $task = new Task();

        $form = $this->createForm(TaskType::class, $task);

        return $this->render('task/new.html.twig', [
            'form' => $form,
        ]);
    }
}