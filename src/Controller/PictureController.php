<?php

namespace App\Controller;

use App\Entity\Dish;
use App\Service\UploaderHelper;
use League\Flysystem\Filesystem;
use App\Repository\PictureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/picture')]
class PictureController extends AbstractController
{
    #[Route('/plat/delete/{id<\d+>?}', name:'app_pic_dish_delete', options:['expose' => true])]
    public function dishDelete(Dish $dish, Request $request, EntityManagerInterface $manager, PictureRepository $pictureRepository)
    {
        $session = $request->getSession();
        // $picturesSession = $session->get('recipe_pictures');

        if($session->has('recipe_picture') && !empty($session->get('recipe_picture'))) 
        {
            // $picture = $picturesSession[$rank];
            // unset($picturesSession[$rank]);
            // $session->set('recipe_pictures', array_values($picturesSession));
            // $picture = $pictureRepository->findOneBy(['id' => $picture->getId()]);

            // dd($this->getParameter('uploads_base_dir').'/'.$dish->getPicturePath());
            $session->remove('recipe_picture');
            unlink($this->getParameter('uploads_base_dir').'/'.$dish->getPicturePath());
            $dish->setPicture(null);
            $manager->persist($dish);
            $manager->flush();

            // $publicUploadsFilesystem->delete(
            //         $requestStackContext
            //                 ->getBasePath().$uploadedAssetsBaseUrl
            //                 .'/'.UploaderHelper::DISH.'/'.$picture->getName()
            // );
        }

        //dd($picture);
        
        if($dish) {
            return $this->redirectToRoute('app_dish_edit', [
                'id' => $dish->getId()
            ]);
        }

        return $this->redirectToRoute('app_dish_new');
    }
}