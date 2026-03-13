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
use Symfony\Component\HttpFoundation\Request;
use App\Service\EnergyAdjustmentManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ProfileController.php
 * 
 * Contrôleur gérant les actions liées au profil utilisateur.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale 
 */
#[Route('/profile')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class ProfileController extends AbstractController implements AlertUserController
{
    /**
     * Affiche la page principale du profil utilisateur.
     *
     * @param EntityManagerInterface $manager Permet d'accéder à la base de données (non utilisé ici mais disponible si besoin)
     *
     * @return Response Rendu de la page profil
     */
    #[Route('', name: 'app_profile_index', methods: ['GET'])]
    public function index(EntityManagerInterface $manager)
    {
        // Rendu de la page profil
        return $this->render("profile/index.html.twig", [
            // Permet d'afficher une modale de confirmation du profil si nécessaire
            'showModalConfirmProfile' => !(empty($showModalConfirmProfile)) ? true : false
        ]);
    }


    /**
     * Édition du profil utilisateur.
     *
     * Si l'utilisateur n'a pas encore commencé à remplir son profil, celui-ci est
     * présenté sous forme de plusieurs sous-formulaires afin de simplifier la saisie.
     *
     * Exemple :
     * Le formulaire principal "user" est découpé en plusieurs étapes :
     * - user_gender
     * - user_birthday
     * - user_height
     * - user_weight etc
     *
     * @param Request $request Requête HTTP contenant les données du formulaire
     * @param EntityManagerInterface $manager Gestionnaire d'entités pour les opérations en base de données
     * @param ValidatorInterface $validator Service de validation des données saisies
     * @param UploaderHelper $uploaderHelper Service gérant l’upload des fichiers (ex : photo de profil)
     * @param ProfileHandler $profileHandler Service gérant les étapes de remplissage du profil
     * @param EnergyAdjustmentManager $energyAdjustmentManager Service ajustant les besoins énergétiques après modification du profil
     * @param string|null $element Élément du profil actuellement en cours d'édition
     *
     * @return Response Retourne la vue du formulaire ou une redirection
     */
    #[Route('/edit/{element?}', name: 'app_profile_edit', methods: ['GET', 'POST'], defaults: ['element' => ProfileHandler::GENDER])]
    public function edit(
        Request $request,
        EntityManagerInterface $manager,
        ValidatorInterface $validator,
        UploaderHelper $uploaderHelper,
        ProfileHandler $profileHandler,
        EnergyAdjustmentManager $energyAdjustmentManager,
        ?string $element
    ): Response {

        /** @var User $user */
        $user = $this->getUser();

        // Création du formulaire de profil.
        // Le formulaire est configuré dynamiquement selon l'élément du profil en cours d'édition
        // (ex : gender, weight, parameters, etc.).
        // Le groupe de validation est également adapté à cet élément.
        $form = $this->createForm(ProfileType::class, $user, [
            'element' => $element,
            'validation_groups' => sprintf('profile_%s', $element)
        ]);

        // Hydrate le formulaire avec les données envoyées dans la requête HTTP
        $form->handleRequest($request);

        // Vérifie si le formulaire a été soumis et si les données sont valides
        if ($form->isSubmitted() && $form->isValid()) {

            // Gestion spécifique lors de l'étape "parameters"
            // (notamment pour la gestion de la photo de profil)
            if (ProfileHandler::PARAMETERS === $element) {

                // Vérifie si un fichier image a été envoyé
                if (null !== $pictureFile = $form->get('pictureFile')->getData()) {

                    // Définition des contraintes de validation pour l'image
                    $pictureConstraint = new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/jpg', 'image/gif', 'image/png'],
                        'mimeTypesMessage' => 'Merci de choisir une image valide',
                    ]);

                    // Validation du fichier uploadé
                    $errorsPic = $validator->validate(
                        $pictureFile,
                        [$pictureConstraint]
                    );

                    // Si une erreur est détectée sur l'image
                    if (isset($errorsPic[0])) {

                        // Stocke le message d'erreur en session
                        $request->getSession()->set('user_error_pic', $errorsPic[0]->getMessage());

                        // Redirige vers l'édition du profil
                        return $this->redirectToRoute('app_profile_edit', ['element' => 'parameters']);
                    }

                    // Si une ancienne photo existe, on la supprime du serveur
                    if ($user->getPicturePath()) {
                        unlink($this->getParameter('uploads_base_dir') . '/' . $user->getPicturePath());
                    }

                    // Upload de la nouvelle image et enregistrement dans l'entité utilisateur
                    $newFilename = $uploaderHelper->upload($pictureFile, UploaderHelper::USER);
                    $user->setPicture($newFilename);
                }
            }

            // Gestion spécifique lorsque l'utilisateur modifie son poids
            if ($element === ProfileHandler::WEIGHT) {

                // Mise à jour de la date du dernier changement de poids
                $user->setLastWeightUpdateAt(new \DateTime());

                // Enregistrement de l'évolution du poids et recalcul des données associées
                $user = $energyAdjustmentManager->logNewWeight($user);
            }

            // Gestion de l'archivage du poids dans l'historique
            if ($form->has('archived_weight')) {

                if ($form->get('archived_weight')->getData()) {

                    // Récupération de l'historique des poids
                    $weights = $user->getWeightEvolution();

                    // Ajout du poids actuel avec la date du jour
                    $weights[date('m/d/Y')] = $user->getWeight();

                    // Mise à jour de l'historique dans l'utilisateur
                    $user->setWeightEvolution($weights);
                }
            }

            // Ajoute l'étape actuelle dans la liste des étapes de profil validées
            if ($element && !in_array($element, $user->getValidStepProfiles())) {
                $user->addValidStepProfiles($element);
            }

            // Si l'énergie est définie et que le profil vient d'être complété pour la première fois
            if (null !== $user->getEnergy() && !$user->getFirstFillProfile()) {

                // Marque le profil comme complété pour la première fois
                $user->setFirstFillProfile(true);

                // Sauvegarde en base
                $manager->persist($user);
                $manager->flush();

                // Affiche une modale de bienvenue
                $this->addFlash('welcome_modal', true);

                // Redirection vers la page d'accueil
                return $this->redirectToRoute('app_homepage');
            }

            // Sauvegarde générale des modifications
            $manager->persist($user);
            $manager->flush();

            // Si le profil n'est pas encore totalement rempli,
            // on redirige vers l'étape suivante du formulaire
            if (!$user->hasFirstFillProfile()) {
                return $this->redirectToRoute('app_profile_edit', [
                    'element' => $profileHandler->currentStep(),
                ]);
            }

            // Message de confirmation
            $this->addFlash('success', 'Vos informations ont bien été enregistrées.');

            // Redirection spécifique après modification de l'énergie
            if ($element === ProfileHandler::ENERGY) {
                return $this->redirectToRoute('app_dashboard_index');
            }

            // Redirection vers la page du profil
            return $this->redirectToRoute('app_profile_index');
        }

        // Si le formulaire n'est pas soumis ou contient des erreurs,
        // on affiche la vue du formulaire
        return $this->render(
            "profile/forms.html.twig",
            [
                'profileForm' => $form->createView(),
                'element' => $element,

                // Route utilisée pour le bouton retour
                'backRoute' => $element === ProfileHandler::ENERGY
                    ? $this->generateUrl('app_dashboard_index')
                    : $this->generateUrl('app_profile_index'),
            ]
        );
    }

    /**
     * Permet l'édition d'un élément du profil utilisateur dans un formulaire embarqué (embedded) pour la modale notamment.
     *
     * Cette action est utilisée lorsque le formulaire de profil est affiché dans un composant
     * partiel (ex : modal ou bloc AJAX) plutôt que sur une page complète.
     *
     * Le formulaire est généré dynamiquement selon l'élément du profil demandé
     * (genre, poids, taille, etc.).
     *
     * @param Request $request Requête HTTP contenant les données du formulaire
     * @param EntityManagerInterface $manager Gestionnaire d'entités pour la persistance en base
     * @param EnergyAdjustmentManager $energyAdjustmentManager Service permettant d'ajuster les besoins énergétiques
     * @param string|null $element Élément du profil à éditer (ex : gender, weight, etc.)
     *
     * @return Response Retourne le rendu du formulaire partiel avec ou sans message de succès
     */
    #[Route('/embedded/edit/{element?}', name: 'app_embedded_profile_edit', methods: ['GET', 'POST'], defaults: ['element' => ProfileHandler::GENDER])]
    public function embeddedEdit(
        Request $request,
        EntityManagerInterface $manager,
        EnergyAdjustmentManager $energyAdjustmentManager,
        ?string $element
    ): Response {

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Création du formulaire de profil correspondant à l'élément à modifier
        // Le groupe de validation est dynamique selon l'élément
        $form = $this->createForm(ProfileType::class, $user, [
            'element' => $element,
            'validation_groups' => sprintf('profile_%s', $element)
        ]);

        // Hydratation du formulaire avec les données de la requête HTTP
        $form->handleRequest($request);

        // Vérifie si le formulaire a été soumis
        if ($form->isSubmitted()) {

            // Si le formulaire contient des erreurs de validation
            if (!$form->isValid()) {

                // Retourne simplement le formulaire avec les erreurs affichées
                return $this->render("profile/partials/_form.html.twig", [
                    'profileForm' => $form->createView(),
                    'element' => $element,
                ]);
            }

            // Traitement spécifique lorsque l'utilisateur modifie son poids
            if ($element === ProfileHandler::WEIGHT) {

                // Mise à jour de la date du dernier changement de poids
                $user->setLastWeightUpdateAt(new \DateTime());

                // Enregistre l'évolution du poids et ajuste les besoins énergétiques
                $user = $energyAdjustmentManager->logNewWeight($user);
            }

            // Persistance des modifications utilisateur
            $manager->persist($user);
            $manager->flush();

            // Message de confirmation après sauvegarde
            $messageSuccess = "Vos informations ont bien été enregistrées";

            // Cas spécifique : si une mise à jour du poids était requise
            if ($request->attributes->has('needs_weight_update')) {

                // Ajoute un message flash de succès
                $this->addFlash('success', $messageSuccess);

                // Redirige vers le dashboard
                return $this->redirectToRoute('app_dashboard_index');
            }

            // Retourne le formulaire avec un message de succès
            return $this->render("profile/partials/_form.html.twig", [
                'profileForm' => $form->createView(),
                'element' => $element,
                'messageSuccess' => "Vos informations ont bien été enregistrées",
            ]);
        }

        // Si le formulaire n'a pas encore été soumis, on affiche simplement le formulaire
        return $this->render("profile/partials/_form.html.twig", [
            'profileForm' => $form->createView(),
            'element' => $element,
        ]);
    }

    /**
     * Permet à l'utilisateur connecté de modifier son mot de passe.
     *
     * @param Request $request Requête HTTP contenant les données du formulaire
     * @param UserPasswordHasherInterface $userPasswordHasher Service permettant de sécuriser le mot de passe via un hash
     * @param EntityManagerInterface $em Gestionnaire d'entités permettant la persistance en base
     *
     * @return Response Retourne la vue du formulaire ou redirige après modification réussie
     */
    #[Route('/change-password', name: 'app_profile_password_edit', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $em
    ): Response {
        // Récupération de l'utilisateur connecté
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Création de l'objet contenant les données du formulaire
        $changePassword = new ChangePassword();

        // Création du formulaire de modification du mot de passe
        $form = $this->createForm(ChangePasswordFormType::class, $changePassword);

        // Hydrate le formulaire avec les données de la requête
        $form->handleRequest($request);

        // Vérifie si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {

            // Hash sécurisé du nouveau mot de passe
            $hashedPassword = $userPasswordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            // Mise à jour du mot de passe utilisateur
            $user->setPassword($hashedPassword);

            // Sauvegarde des modifications en base de données
            $em->persist($user);
            $em->flush();

            // Ajoute un message flash de confirmation
            $this->addFlash('success', 'Votre mot de passe a bien été modifié');

            // Redirige vers la page profil
            return $this->redirectToRoute('app_profile_index');
        }

        // Affiche le formulaire de changement de mot de passe
        // Si le formulaire est soumis mais invalide, retourne un code HTTP 422
        return $this->render(
            "profile/change_password.html.twig",
            [
                "passwordForm" => $form->createView()
            ],
            new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200)
        );
    }

    /**
     * Affiche la barre de progression du profil utilisateur.
     *
     * Cette méthode récupère le pourcentage de complétion du profil
     * via le service ProfileHandler et retourne un fragment Twig
     * à afficher dans la vue.
     *
     * @param ProfileHandler $profileHandler Service gérant la logique du profil
     *
     * @return Response
     */
    #[Route('/progressbar', name: 'app_profile_progress_bar', methods: ['GET'])]
    public function progressBar(ProfileHandler $profileHandler): Response
    {
        return $this->render('profile/partials/_progress_bar_ratio.html.twig', [
            'ratio' => $profileHandler->proportionCompleted(),
        ]);
    }

    /**
     * Permet à un administrateur de modifier les rôles de tous les utilisateurs.
     *
     * @param Request $request Objet représentant la requête HTTP
     * @param UserRepository $userRepository Repository pour accéder aux utilisateurs
     * @param EntityManagerInterface $manager Gestionnaire d'entités pour la persistance
     *
     * @return Response
     */
    #[Route('/users/roles/edit', name: 'app_users_roles_edit', methods: ['GET', 'POST'])]
    public function editUserRoles(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $manager
    ): Response {
        // Sécurisation : seul un administrateur peut accéder à cette page
        $this->denyAccessUnlessGranted('ROLE_ADMIN_ROLE');

        // Récupération de tous les utilisateurs
        $users = $userRepository->findAll();

        $formRoleUserViews = [];

        // Création d'un formulaire pour chaque utilisateur
        foreach ($users as $user) {
            $formRoleUserViews[$user->getUsername()] = $this->createForm(RoleUserFormType::class, $user);
        }

        // Si le formulaire a été soumis
        if ($request->isMethod('POST')) {
            $datas = $request->request->all()['role_user_form'] ?? null;

            if ($datas && isset($datas['id'], $datas['roles'])) {
                // Recherche de l'utilisateur ciblé
                $user = $userRepository->findOneBy(['id' => $datas['id']]);

                if ($user) {
                    // Mise à jour des rôles
                    $user->setRoles($datas['roles']);
                    $manager->persist($user);
                    $manager->flush();
                }
            }
        }

        // Transformation de chaque formulaire en vue Twig
        array_walk($formRoleUserViews, function (&$value, $key) {
            $value = $value->createView();
        });

        // Rendu du template avec tous les formulaires
        return $this->render('users/update_roles.html.twig', [
            'formRoleUserViews' => $formRoleUserViews,
        ]);
    }

    /**
     * Exporte les rôles d'un utilisateur sous forme de fichier JSON téléchargeable.
     *
     * @param Request $request Objet représentant la requête HTTP
     * @param User $user Utilisateur dont on veut exporter les rôles
     * @param SluggerInterface $slugger Service pour créer un slug sûr pour le nom de fichier
     *
     * @return Response Fichier JSON téléchargeable contenant les rôles de l'utilisateur
     */
    #[Route(
        '/users/roles/export/{username}',
        name: 'app_users_roles_export',
        methods: ['GET'],
        requirements: ['username' => '.+']
    )]
    public function exportUserRoles(
        Request $request,
        User $user,
        SluggerInterface $slugger
    ): Response {
        // Conversion des rôles en JSON
        $jsonRoles = json_encode($user->getRoles());

        // Création de la réponse HTTP avec le bon Content-Type
        $response = new Response($jsonRoles, 200, [
            'Content-Type' => 'application/json'
        ]);

        // Création du header pour forcer le téléchargement
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            sprintf('roles_%s.json', $slugger->slug($user->getUsername()))
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * Affiche le bloc d'énergie d'un profil utilisateur.
     *
     * @param EnergyHandler $energyHandler Service gérant les calculs liés à l'énergie
     * @param AlertFeature $alertFeature Service gérant les alertes liées à l'énergie
     *
     * @return Response
     */
    #[Route('/energy', name: 'app_users_energy', methods: ['GET'])]
    public function energy(EnergyHandler $energyHandler, AlertFeature $alertFeature): Response
    {
        return $this->render('profile/partials/_energy.html.twig', [
            // Liste des éléments manquants pour compléter l'énergie du profil
            'missingElements' => $energyHandler->profileMissingForEnergy(),
            // Alertes liées à l'énergie (ex: seuils, notifications)
            'balanceEnergyAlerts' => $alertFeature->getEnergyAlert(),
        ]);
    }

    /**
     * Affiche les alertes liées au poids de l'utilisateur.
     *
     * @param EnergyHandler $energyHandler Service pour gérer la logique liée à l'énergie (non utilisé ici mais injecté)
     * @param AlertFeature $alertFeature Service pour récupérer les alertes
     *
     * @return Response Rendu du template Twig pour le poids
     */
    #[Route('/weight', name: 'app_users_weight', methods: ['GET'])]
    public function weight(EnergyHandler $energyHandler, AlertFeature $alertFeature): Response
    {
        return $this->render('profile/partials/_weight.html.twig', [
            'balanceWeightAlerts' => $alertFeature->getWeightAlert(),
        ]);
    }

    /**
     * Affiche les alertes liées à l'IMC de l'utilisateur.
     *
     * @param EnergyHandler $energyHandler Service pour gérer la logique liée à l'énergie (non utilisé ici)
     * @param AlertFeature $alertFeature Service pour récupérer les alertes
     *
     * @return Response Rendu du template Twig pour l'IMC
     */
    #[Route('/imc', name: 'app_users_imc', methods: ['GET'])]
    public function imc(EnergyHandler $energyHandler, AlertFeature $alertFeature): Response
    {
        return $this->render('profile/partials/_imc.html.twig', [
            'balanceImcAlerts' => $alertFeature->getImcAlert($this->getUser()->getImc()),
        ]);
    }

    /**
     * Supprime la photo de profil de l'utilisateur connecté.
     *
     * @param Request $request Objet représentant la requête HTTP
     * @param EntityManagerInterface $manager Gestionnaire d'entités
     *
     * @return Response Redirection vers l'édition du profil
     */
    #[Route('/remove-picture', name: 'app_users_remove_pic', methods: ['GET', 'POST'])]
    public function removePicture(Request $request, EntityManagerInterface $manager): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Suppression du fichier sur le serveur
        unlink($this->getParameter('uploads_base_dir') . '/' . $user->getPicturePath());

        // Mise à jour de l'utilisateur
        $user->setPicture(null);
        $manager->persist($user);
        $manager->flush();

        // Message flash de confirmation
        $this->addFlash('notice', 'La photo a bien été supprimée');

        // Redirection vers l'édition du profil (section "paramètres")
        return $this->redirectToRoute('app_profile_edit', [
            'element' => ProfileHandler::PARAMETERS,
        ]);
    }

    /**
     * Affiche un message d'erreur lié à la photo de profil.
     *
     * @param Request $request Objet représentant la requête HTTP
     *
     * @return Response Message d'erreur
     */
    #[Route('/show-error-picture', name: 'app_users_show_error_picture', methods: ['GET'])]
    public function showErrorPicture(Request $request): Response
    {
        // Récupère le message stocké en session
        $message = $request->getSession()->get('user_error_pic');
        // Supprime le message de la session pour éviter les doublons
        $request->getSession()->remove('user_error_pic');

        // Retourne le message brut dans la réponse HTTP
        return new Response($message);
    }
}
