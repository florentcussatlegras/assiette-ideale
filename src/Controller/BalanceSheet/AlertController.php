<?php

namespace App\Controller\BalanceSheet;

use App\Service\AlertFeature;
use App\Entity\Alert\LevelAlert;
use App\Service\BalanceSheetFeature;
use App\Controller\AlertUserController;
use App\Repository\FoodGroupParentRepository;
use App\Service\NutrientHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


/**
 * AlertController.php
 *
 * Controller gérant les alertes nutritionnelles et énergétiques pour l'utilisateur.
 *
 * Toutes les routes de ce controller nécessitent que l'utilisateur soit authentifié.
 * Ce controller implémente AlertUserController pour respecter le contrat des alertes utilisateurs.
 *
 * Routes principales :
 * - /balance_sheet/alert/show-imc-alert          -> Affiche l'alerte IMC
 * - /balance_sheet/alert/show-weight-alert       -> Affiche l'alerte poids
 * - /balance_sheet/alert/show-average-data       -> Moyennes nutritionnelles sur une période
 * - /balance_sheet/alert/message-details-alert-* -> Détails des alertes énergétiques, nutriments et FGP
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 *
 * @package App\Controller\BalanceSheet
 */
#[Route('/balance_sheet/alert', name: 'app_balance_sheet_')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class AlertController extends AbstractController implements AlertUserController
{
    /**
     * Affiche l'alerte liée à l'IMC de l'utilisateur.
     *
     * @param AlertFeature $alertFeature Service pour calculer les alertes
     * @return Response Vue contenant le message d'alerte IMC
     */
    #[Route('/show-imc-alert', name: 'show_imc_alert', methods: ['GET'])]
    public function showImcAlert(AlertFeature $alertFeature)
    {
        /** @var App\Entity\User|null $user */
        $user = $this->getUser();

        // Récupère le niveau d'alerte IMC calculé par le service
        $levelAlert = $alertFeature->getImcAlert($user->getImc());
        $message = $this->getAlertMessage('imc', $levelAlert);

        return $this->render('balance_sheet/_imc_weight_alert.html.twig', [
            'levelAlert' => $levelAlert,
            'message' => $message ?? null,
        ]);
    }

    /**
     * Affiche l'alerte liée au poids de l'utilisateur.
     *
     * @param AlertFeature $alertFeature Service pour calculer les alertes
     * @return Response Vue contenant le message d'alerte poids
     */
    #[Route('/show-weight-alert', name: 'show_weight_alert', methods: ['GET'])]
    public function showWeightAlert(AlertFeature $alertFeature)
    {
        // Récupère le niveau d'alerte du poids
        $levelAlert = $alertFeature->getWeightAlert();
        $message = $this->getAlertMessage('weight', $levelAlert);

        return $this->render('balance_sheet/_imc_weight_alert.html.twig', [
            'levelAlert' => $levelAlert,
            'message' => $message ?? null,
        ]);
    }

    /**
     * Calcule et affiche les moyennes nutritionnelles sur une période donnée.
     *
     * @param Request $request Requête HTTP contenant éventuellement 'start' et 'end'
     * @param BalanceSheetFeature $balanceSheetFeature Service pour calculer les moyennes nutritionnelles
     * @param AlertFeature $alertFeature Service pour générer les alertes nutritionnelles
     * @param FoodGroupParentRepository $foodGroupParentRepository Repository pour récupérer les informations FGP
     * @return Response Vue avec les données nutritionnelles moyennes et alertes
     */
    #[Route('/show-average-data', name: 'show_average_data', methods: ['GET'])]
    public function averageData(Request $request, BalanceSheetFeature $balanceSheetFeature, AlertFeature $alertFeature, FoodGroupParentRepository $foodGroupParentRepository)
    {
        /** @var App\Entity\User|null $user */
        $user = $this->getUser();

        // Vérifie si une période est envoyée dans l'URL
        if ($request->query->get('start') && $request->query->get('end')) {

            $start = $request->query->get('start');
            $end = $request->query->get('end');

            // Calcul de la moyenne énergétique
            $averageDailyEnergy = $balanceSheetFeature->averageDailyEnergyForAPeriod($start, $end);

            // Si aucun repas n'est trouvé
            if (!$averageDailyEnergy) {

                $start = new \DateTime($start);
                $end = new \DateTime($end);

                $params = [
                    'start_date' => $start->format('d M Y'),
                    'end_date' => $end->format('d M Y'),
                    'no_meals' => true,
                ];
            } else {

                // Calcul des moyennes nutritionnelles
                $averageDailyNutrient = $balanceSheetFeature->averageDailyNutrientForAPeriod($start, $end);

                // Valeurs recommandées pour l'utilisateur
                $accessor = PropertyAccess::createPropertyAccessor();

                $averageDailyNutrientRecommended = array_combine(
                    NutrientHandler::NUTRIENTS,
                    array_map(fn($nutrient) => $accessor->getValue($user, $nutrient), NutrientHandler::NUTRIENTS)
                );

                // Moyenne des groupes alimentaires
                $averageDailyFgp = $balanceSheetFeature->averageDailyFgpForAPeriod($start, $end);

                // Génère les alertes nutritionnelles
                $balanceSheetAlerts = $alertFeature->getBalanceSheetAlerts(
                    $averageDailyEnergy,
                    $averageDailyNutrient
                );

                $start = new \DateTime($start);
                $end = new \DateTime($end);

                // Paramètres envoyés à la vue
                $params = [
                    'start_date' => $start->format('d M Y'),
                    'end_date' => $end->format('d M Y'),
                    'average_energy' => $averageDailyEnergy ?? 0,
                    'average_nutrient' => $averageDailyNutrient ?? 0,
                    'average_nutrient_recommended' => $averageDailyNutrientRecommended,
                    'average_fgp' => $averageDailyFgp ?? 0,
                    'average_fgp_recommended' => $user->getRecommendedQuantities(),
                    'alias_metadata_map_fgp' => $foodGroupParentRepository->getAliasMetadataMap(),
                    'balance_sheet_alerts' => $balanceSheetAlerts,
                ];
            }
        }

        // Si la méthode renvoie une Response (ex: redirection)
        if (isset($balanceSheetAlerts) && $balanceSheetAlerts instanceof Response) {
            return $balanceSheetAlerts;
        }

        // Si requête AJAX -> renvoie seulement le contenu
        if ($request->query->get('ajax')) {
            return $this->render('balance_sheet/_content.html.twig', $params);
        }

        // Sinon affiche la page complète
        return $this->render('balance_sheet/index.html.twig', $params);
    }

    /**
     * Affiche le détail d'une alerte liée à la consommation énergétique (calories).
     *
     * @param Request $request Requête HTTP
     * @param AlertFeature $alertFeature Service pour calculer les alertes
     * @param int|null $energy Energie consommée (optionnel, récupérée depuis la session si null)
     * @return Response Vue avec le message d'alerte énergétique
     */
    #[Route('/message-details-alert-energy/{energy?}', name: 'message_details_alert_energy', methods: ['GET'])]
    public function messageDetailsAlertEnergy(Request $request, AlertFeature $alertFeature, ?int $energy)
    {
        /** @var App\Entity\User|null $user */
        $user = $this->getUser();

        // Récupère la session utilisateur
        $session = $request->getSession();

        // Si l'énergie n'est pas passée dans l'URL,
        // on récupère la valeur stockée en session (_meal_day_energy)
        // sinon on utilise la valeur passée en paramètre
        $mealDayEnergy = null === $energy ? $session->get('_meal_day_energy') : $energy;

        // Calcule la différence entre l'énergie recommandée pour l'utilisateur
        // et l'énergie réellement consommée dans la journée
        // abs() permet d'obtenir une valeur positive
        $remainingMealDayEnergy = abs(round($user - $mealDayEnergy));

        // Vérifie si la consommation est équilibrée ou non
        // en comparant l'énergie consommée et l'énergie recommandée
        $alert = $alertFeature->isWellBalanced($mealDayEnergy, $user);

        // Si la consommation calorique est correcte
        if (LevelAlert::BALANCE_WELL === $alert->getCode()) {

            $title = "Super, vous êtes bon !";
            // Message indiquant que la consommation est correcte
            $message = "Votre consommation calorique est bonne";
        } elseif (in_array($alert->getCode(), LevelAlert::LOW_ALERTS, true)) {

            $title = "Consommez plus !";
            // Indique le nombre de calories supplémentaires nécessaires
            $message = "Vous devriez consommer environ $remainingMealDayEnergy Kcal supplémentaire";
        } else {

            $title = "Consommez moins !";
            // Indique le dépassement par rapport aux recommandations
            $message = "Vous dépassez d'environ $remainingMealDayEnergy Kcal nos recommendations";
        }

        return $this->render('balance_sheet/_message_details_alert.html.twig', [
            'title' => $title,
            'message' => $message ?? null,
        ]);
    }

    /**
     * Affiche le détail d'une alerte pour un groupe alimentaire (Food Group Parent).
     *
     * @param Request $request Requête HTTP
     * @param FoodGroupParentRepository $foodGroupParentRepository Repository pour récupérer les FGP
     * @param string $fgpAlias Alias du groupe alimentaire
     * @param int $quantity Quantité consommée
     * @param string $alertCode Code de l'alerte (BALANCE_WELL, BALANCE_LACK, etc.)
     * 
     * @return Response Vue avec le message d'alerte FGP
     */
    #[Route('/message-details-alert-fgp/{fgpAlias}/{quantity}/{alertCode}', name: 'message_details_alert_fgp', methods: ['GET'])]
    public function messageDetailsAlertFgp(FoodGroupParentRepository $foodGroupParentRepository, string $fgpAlias, int $quantity, string $alertCode)
    {
        /** @var App\Entity\User|null $user */
        $user = $this->getUser();

        // Récupère l'entité du groupe alimentaire parent à partir de son alias
        $fgp = $foodGroupParentRepository->findOneByAlias($fgpAlias);

        // Récupère la quantité recommandée pour ce groupe alimentaire depuis les données de l'utilisateur
        $quantRecommended = $user->getRecommendedQuantities()[$fgpAlias];

        // Génère le message d'alerte détaillé en fonction de la quantité consommée et du niveau d'alerte
        $alertDetail = $this->getAlertDetailMessage($fgp->getName(), $quantity, $quantRecommended, $alertCode);

        return $this->render('balance_sheet/_message_details_alert.html.twig', $alertDetail);
    }

    /**
     * Affiche le détail d'une alerte sur un nutriment (protéine, lipide, glucide, sodium).
     *
     * @param Request $request Requête HTTP
     * @param string $nutrient Nom du nutriment
     * @param int $quantity Quantité consommée
     * @param string $alertCode Code de l'alerte (BALANCE_WELL, BALANCE_LACK, etc.)
     * 
     * @return Response Vue avec le message d'alerte nutriment
     */
    #[Route('/message-details-alert-nutrient/{nutrient}/{quantity}/{alertCode}', name: 'message_details_alert_nutrient', methods: ['GET'])]
    public function messageDetailsAlertNutrient(NutrientRepository $nutrientRepository, string $nutrient, int $quantity, string $alertCode)
    {
        $nutrientObject = $nutrientRepository->findOneByCode($nutrient);

        if (!$nutrientObject) {
            $this->createNotFoundException(sprintf(
                'Le nutriment "%s" est introuvable.',
                $nutrient
            ));
        }

        /** @var App\Entity\User|null $user */
        $user = $this->getUser();

        // Création d'un PropertyAccessor pour accéder dynamiquement aux propriétés de $user
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        // Récupère la valeur recommandée pour le nutriment demandé
        $quantRecommended = (int) $propertyAccessor->getValue($user, $nutrient);

        // Génère le message d'alerte détaillé selon la quantité consommée et le niveau d'alerte
        $alertDetail = $this->getAlertDetailMessage($nutrientObject->getName(), $quantity, $quantRecommended, $alertCode);

        return $this->render('balance_sheet/_message_details_alert.html.twig', $alertDetail);
    }

    /**
     * Retourne le message d'alerte pour un type et un niveau donné
     *
     * @param string $type 'imc' ou 'weight'
     * @param LevelAlert $levelAlert Niveau d'alerte
     * 
     * @return TranslatableMessage
     */
    private function getAlertMessage(string $type, LevelAlert $levelAlert): TranslatableMessage
    {
        /** @var App\Entity\User|null $user */
        $user = $this->getUser();

        // Détermine la valeur idéale à utiliser dans le message.
        // Si le type est 'imc' (indice de masse corporelle), on prend l'IMC idéal de l'utilisateur
        // sinon on prend le poids idéal. La valeur est arrondie à l'entier pour plus de lisibilité.
        $idealValue = $type === 'imc'
            ? (int) round($user->getIdealImc())
            : round($user->getIdealWeight());

        // Définition d'une correspondance entre le code d'alerte et la clé de traduction.
        // Les clés suivent un format cohérent "alert.{type}.balance_*", ce qui permet d'utiliser
        // la même structure pour IMC et poids et facilite la traduction.
        $messagesMap = [
            LevelAlert::BALANCE_WELL => "alert.$type.balance_well",               // Consommation ou valeur correcte
            LevelAlert::BALANCE_LACK => "alert.$type.balance_lack",               // Légère insuffisance
            LevelAlert::BALANCE_VERY_LACK => "alert.$type.balance_very_lack",     // Insuffisance importante
            LevelAlert::BALANCE_CRITICAL_LACK => "alert.$type.balance_critical_lack", // Insuffisance critique
            LevelAlert::BALANCE_EXCESS => "alert.$type.balance_excess",           // Légère surconsommation / excès
            LevelAlert::BALANCE_VERY_EXCESS => "alert.$type.balance_very_excess", // Excès important
            LevelAlert::BALANCE_CRITICAL_EXCESS => "alert.$type.balance_critical_excess", // Excès critique
        ];

        $translationKey = $messagesMap[$levelAlert->getCode()] ?? null;

        return new TranslatableMessage($translationKey, [$type === 'imc' ? 'imc_ideal' : 'weight_ideal' => $idealValue], 'alert');
    }

    /**
     * Retourne le titre et le message pour un alertCode donné et un item consommé.
     *
     * @param string $itemName Nom à afficher (ex : fruit, protéine)
     * @param float|int $quantity Consommé
     * @param float|int $quantityRecommended Quantité recommandée
     * @param string $alertCode Code de l'alerte (BALANCE_WELL, BALANCE_LACK, etc.)
     * 
     * @return array{title:string, message:string} Tableau contenant le titre et le message
     */
    private function getAlertDetailMessage(string $itemName, float|int $quantity, float|int $quantityRecommended, string $alertCode): array
    {
        // Calcul de la différence absolue entre la quantité recommandée et la quantité réellement consommée.
        // La fonction round() arrondit la valeur au nombre entier le plus proche, et abs() garantit
        // que le résultat est positif, quelle que soit la situation (surconsommation ou déficit).
        $remaining = abs(round($quantityRecommended - $quantity));

        // Transformation du nom de l'élément en minuscules pour un affichage plus naturel dans les messages.
        $itemNameLower = strtolower($itemName);

        // Initialisation des variables qui contiendront le titre et le message de l'alerte.
        $title = '';
        $message = '';

        // Sélection du texte d'alerte en fonction du code d'alerte fourni.
        // Le code d'alerte représente différents niveaux de consommation : 
        // équilibre, manque, excès, ou niveaux critiques.
        switch ($alertCode) {

            case LevelAlert::BALANCE_WELL:
                // Cas où la consommation est correcte / équilibrée
                $title = "Super, vous êtes bon en {$itemNameLower} !";
                $message = "Votre consommation quotidienne de {$itemNameLower} est bonne";
                break;

            case LevelAlert::BALANCE_LACK:
                // Cas où la consommation est légèrement insuffisante
                $title = "Consommez plus de {$itemNameLower} !";
                $message = "Consommez-en quotidiennement environ $remaining g supplémentaire";
                break;

            case LevelAlert::BALANCE_VERY_LACK:
                // Cas où la consommation est trop faible
                $title = "Votre consommation de {$itemNameLower} est trop faible!";
                $message = "Consommez-en quotidiennement environ $remaining g supplémentaire";
                break;

            case LevelAlert::BALANCE_CRITICAL_LACK:
                // Cas critique : la consommation est extrêmement faible
                $title = "Votre consommation de {$itemNameLower} est beaucoup trop faible!";
                $message = "Mangez-en beaucoup plus! ($remaining g supplémentaire)";
                break;

            case LevelAlert::BALANCE_EXCESS:
                // Cas où la consommation est légèrement supérieure à la recommandation
                $title = "Consommez moins de {$itemNameLower} !";
                $message = "Vous consommez quotidiennement $remaining g en trop";
                break;

            case LevelAlert::BALANCE_VERY_EXCESS:
                // Cas où la consommation est trop élevée
                $title = "Votre consommation de {$itemNameLower} est trop importante!";
                $message = "Baissez votre consommation d'environ $remaining g";
                break;

            case LevelAlert::BALANCE_CRITICAL_EXCESS:
                // Cas critique : la consommation est beaucoup trop élevée
                $title = "Votre consommation de {$itemNameLower} est beaucoup trop importante!";
                $message = "Mangez-en beaucoup moins! ($remaining g de moins)";
                break;
        }

        // Retourne un tableau associatif contenant le titre et le message adaptés au niveau d'alerte
        // Ces informations peuvent être affichées à l'utilisateur dans l'interface.
        return ['title' => $title, 'message' => $message];
    }
}
