<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EnergyHandler;
use App\Service\AlertFeature;
use App\Service\ProfileHandler;
use App\Service\UploaderHelper;
use App\Form\Model\ChangePassword;
use App\Repository\UserRepository;
use App\Form\Type\RoleUserFormType;
use App\Form\Type\Profile\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Type\ChangePasswordFormType;
use App\Form\Type\Profile\FirstProfileType;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\PhysicalActivityRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\String\Slugger\SluggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Route("/profile")
 */
class ProfileController extends AbstractController implements AlertUserController
{
    /**
     * @Route("", name="app_profile_index")
     */
    public function index(EntityManagerInterface $manager)
    {
        //dump($this->getUser());
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();

        // if($user->getEnergy() && !$user->hasStartedFillProfile())
        // {
        //     $user->setStartedFillProfile(true);
        //     $manager->persist($user);
        //     $manager->flush();

        //     $showModalConfirmProfile = true;
        // }

        return $this->render("profile/index.html.twig", [
            'showModalConfirmProfile' => !(empty($showModalConfirmProfile)) ? true : false
        ]);
    }


    // // Si l'utilisateur n'a pas encore saisi son profil on utilise les formulaires de profile plus détaillés avec des sous-étapes
    // // e.g. le formulaire "user" est partitionné en plusieurs petites formulaires "user_gender", "user_birthday", "user_height", "user_weight"
    // // si la sous étape est nulle on l'initialise à gender (premier étape où l'utilisateur choisit son genre)
    // if(!$user->hasStartedFillProfile() && ProfileHandler::STEP_PHYSICAL_CHARACTERISTICS === $step && null === $subStep) {
    //     $subStep = ProfileHandler::STEP_PHYSICAL_CHARACTERISTICS_GENDER;
    // }
     #[Route('/edit/{element?}', name:'app_profile_edit', defaults: ['element' => ProfileHandler::GENDER])]
    public function edit(Request $request, EntityManagerInterface $manager, ValidatorInterface $validator, PhysicalActivityRepository $physicalActivityRepository, UploaderHelper $uploaderHelper, ProfileHandler $profileHandler, ?string $element, bool $firstFillProfile = false)
    {
        if($this->isGranted('IS_AUTHENTICATED') && !$this->getUser()->isIsVerified()) {
            return $this->redirectToRoute('app_verify_resend_email', [
                'id' => $this->getUser()->getId(),
            ]);
        }

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $user = $this->getUser();
        // dd(empty($user->getForbiddenFoods()->toArray()));
        // dd($element);

        // if(!$user->getEnergy()) {
        //     $accessor = PropertyAccess::createpropertyAccessor();
        //     $value = $accessor->getValue($user, $element);
        //     // dump($element);
        //     // dd($value);
        //     if($value instanceof \Traversable){
        //         $value = empty($value->toArray()) ?? null;
        //     }
        //     // dd($element, $value, $profileHandler->nextElement($element));
        //     // dd($profileHandler->nextElement($element));
        //     // dd($element);

        //     // if(null !== $value) {
        //     //     return $this->redirectToRoute('app_profile_edit', [
        //     //         'element' => $profileHandler->nextElement($element),
        //     //     ]);
        //     // }
        // }

        // dd($request->query->all());
        // if($request->query->get('first_fill_profile')) {
        //     $user->setFirstFillProfile(true);
        //     $manager->flush();

        //     return $this->redirectToRoute('app_dashboard_index');
        // }

        $form = $this->createForm(ProfileType::class, $user, [
            'element' => $element,
            'validation_groups' => sprintf('profile_%s', $element)
        ]);

        $nextElement = $profileHandler->nextElement($element);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            if(ProfileHandler::PARAMETERS === $element) {

                if(null !== $pictureFile = $form->get('pictureFile')->getData()){

                    $pictureConstraint = new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/jpg', 'image/gif', 'image/png'],
                        'mimeTypesMessage' => 'Merci de choisir une image valide',
                    ]);

                    $errorsPic = $validator->validate(
                        $pictureFile,
                        [$pictureConstraint]
                    );
                    // dd($errorsPic);
                    if(isset($errorsPic[0])) {
                        $request->getSession()->set('user_error_pic', $errorsPic[0]->getMessage());

                        return $this->redirectToRoute('app_profile_edit', ['element' => 'parameters']);
                    }

                    if($user->getPicturePath()) {
                        unlink($this->getParameter('uploads_base_dir').'/'.$user->getPicturePath());
                    }
                    $newFilename = $uploaderHelper->upload($pictureFile, UploaderHelper::USER);
                    $user->setPicture($newFilename);
                }
            }

            if($form->has('archived_weight')) {

                if($form->get('archived_weight')->getData()) {

                    $weights = $user->getWeightEvolution();
                    // $weights = [
                    //     '02/14/2022' => 78,
                    //     '08/15/2022' => 86,
                    //     '12/31/2022' => 87,
                    //     '02/14/2023' => 63,
                    //     '08/15/2023' => 89,
                    //     '12/31/2023' => 79,
                    // ];
                    $weights[date('m/d/Y')] = $user->getWeight();
                    $user->setWeightEvolution($weights);

                }

            }

            if($element && !in_array($element, $user->getValidStepProfiles())) {
                $user->addValidStepProfiles($element);
            }

            if(null !== $user->getEnergy()) {
                $user->setFirstFillProfile(true);
            }

            $manager->persist($user);
            $manager->flush();
            // dd($nextElement);

            if(!$user->hasFirstFillProfile() && $nextElement) {
                // dd($nextElement);
                return $this->redirectToRoute('app_profile_edit', [
                    'element' => $nextElement
                ]);
            }

            $this->addFlash('success', 'Vos informations ont bien été enregistrées.');

            return $this->redirectToRoute('app_profile_index');
        }

        return $this->render("profile/forms.html.twig", [
                'profileForm' => $form->createView(),
                'element' => $element,
                // 'steps' => ProfileHandler::STEPS,
                'nextElement' => $nextElement,
            ]
        );
    }

    /**
     * @Route("/change-password", name="app_profile_password_edit")
     */
    public function changePassword(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $changePassword = new ChangePassword();
        $form = $this->createForm(ChangePasswordFormType::class, $changePassword);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $user = $this->getUser();

            $hashedPassword = $userPasswordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Votre mot de passe a bien été modifié');

            return $this->redirectToRoute('app_profile_index');
        }

        return $this->render("profile/change_password.html.twig", [
            "passwordForm" => $form->createView()
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));
    }

    /**
     * @Route("/progressbar", name="app_profile_progress_bar")
     */
    public function progressBar(ProfileHandler $profileHandler)
    {
        return $this->render('profile/partials/_progress_bar_ratio.html.twig', [
            'ratio' => $profileHandler->proportionCompleted()
        ]);
    }

    /**
     * @Route("/users/roles/edit", name="app_users_roles_edit")
     */
    public function editUserRoles(Request $request, UserRepository $userRepository, EntityManagerInterface $manager)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN_ROLE');

        $users = $userRepository->findAll();

        foreach($users as $user)
        {
            $formRoleUserViews[$user->getUsername()] = $this->createForm(RoleUserFormType::class, $user);
        }

        if($request->isMethod('POST')) {
            $datas = $request->request->all()['role_user_form'];
            $user = $userRepository->findOneBy(['id' => $datas['id']]);
            $user->setRoles($datas['roles']);
            $manager->persist($user);
            $manager->flush();
        }

        array_walk($formRoleUserViews, function(&$value, $key){
            $value = $value->createView();
        });

        return $this->render('users/update_roles.html.twig', [
            'formRoleUserViews' => $formRoleUserViews
        ]);
    }

    /**
     * @Route("/users/roles/export/{username}", name="app_users_roles_export")
     */
    public function exportUserRoles(Request $request, User $user, SluggerInterface $slugger)
    {
        $response = new Response(json_encode($user->getRoles()), 200, [
            'Content-Type' => 'application/json'
        ]);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            sprintf('roles_%s', $slugger->slug($user->getUsername()))
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/energy', name: 'app_users_energy')]
    public function energy(EnergyHandler $energyHandler, AlertFeature $alertFeature)
    {
        return $this->render('profile/partials/_energy.html.twig', [
            'missingElements' => $energyHandler->profileMissingForEnergy(),
            'balanceEnergyAlerts' => $alertFeature->getEnergyAlert(),
        ]);
    }

    #[Route('/weight', name: 'app_users_weight')]
    public function weight(EnergyHandler $energyHandler, AlertFeature $alertFeature)
    {
        return $this->render('profile/partials/_weight.html.twig', [
            'balanceWeightAlerts' => $alertFeature->getWeightAlert(),
        ]);
    }

    #[Route('/imc', name: 'app_users_imc')]
    public function imc(EnergyHandler $energyHandler, AlertFeature $alertFeature)
    {
        return $this->render('profile/partials/_imc.html.twig', [
            'balanceImcAlerts' => $alertFeature->getImcAlert(),
        ]);
    }

    #[Route('/remove-picture', name: 'app_users_remove_pic')]
    public function removePicture(Request $request, EntityManagerInterface $manager): Response
    {
        $user = $this->getUser();

        unlink($this->getParameter('uploads_base_dir').'/'.$user->getPicturePath());
        $user->setPicture(null);
        $manager->persist($user);
        $manager->flush();

        $this->addFlash('notice', 'La photo a bien été supprimée');

        return $this->redirectToRoute('app_profile_edit', [
            'element' => ProfileHandler::PARAMETERS,
        ]);
    }

    #[Route('/show-error-picture')]
    public function showErrorPicture(Request $request): Response
    {
        $message = $request->getSession()->get('user_error_pic');
        $request->getSession()->remove('user_error_pic');

        return new Response($message);
    }
}