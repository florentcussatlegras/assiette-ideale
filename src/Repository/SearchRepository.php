<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ArrayParameterType;

class SearchRepository
{
    public function __construct(
        private Connection $connection
    ) {}

    public function searchFoodAndDish(
        ?string $keyword,
        array|string|null $fglist = [],
        bool $freeLactose = false,
        bool $freeGluten = false,
        ?string $typeItem = null,
        int $limit = 12,
        int $offset = 0,
    ): array {

        if (!is_array($fglist) && !empty($fglist) && $fglist !== 'none') {
            $fglist = array_map('intval', explode(',', $fglist));
        }

        $typeItem = $typeItem ? array_map('trim', explode(',', $typeItem)) : [];

        $includeFood = empty($typeItem) || in_array('food', $typeItem);
        $includeDish = empty($typeItem) || in_array('dish', $typeItem);

        $params = [];
        $types  = [];

        /*
        |--------------------------------------------------------------------------
        | FOOD QUERY
        |--------------------------------------------------------------------------
        */

        if ($includeFood) {

            $foodSql = "
                    SELECT 
                        f.id,
                        f.name,
                        'Food' AS item_type,
                        f.picture,
                        f.slug,
                        fgp.id AS parent_id,
                        fgp.color AS parent_color,
                        GROUP_CONCAT(DISTINCT um.id) AS unit_measure_ids,
                        GROUP_CONCAT(DISTINCT um.alias) AS unit_measure_aliases,
                        NULL as dish_food_group_parent_ids,
                        NULL as dish_food_group_parent_colors
                    FROM food f
                    LEFT JOIN food_group fg ON fg.id = f.food_group_id
                    LEFT JOIN food_group_parent fgp ON fgp.id = fg.parent_id
                    LEFT JOIN food_unit_measure fum ON fum.food_id = f.id
                    LEFT JOIN unit_measure um ON um.id = fum.unit_measure_id
                    WHERE 1=1
            ";

            /*
            |--------------------------------------------------------------------------
            | FOOD FILTERS
            |--------------------------------------------------------------------------
            */

            if (!empty($keyword)) {
                $foodSql .= " AND f.name LIKE :keyword ";
                $params['keyword'] = '%'.trim($keyword).'%';
            }

            if ($freeGluten) {
                $foodSql .= " AND f.have_gluten = 0 ";
            }

            if ($freeLactose) {
                $foodSql .= " AND f.have_lactose = 0 ";
            }

            if (!empty($fglist) && $fglist !== 'none') {
                $foodSql .= " AND fg.id IN (:fglist_food) ";
                $params['fglist_food'] = $fglist;
                $types['fglist_food']  = ArrayParameterType::INTEGER;
            }

            // if (!empty($foodsNotAllowed)) {
            //     // dd($foodsNotAllowed);
            //     $foodSql .= " AND f.id NOT IN (:foodsNotAllowed_food) ";
            //     $params['foodsNotAllowed_food'] = $foodsNotAllowed;
            //     $types['foodsNotAllowed_food']  = ArrayParameterType::INTEGER;
            // }

            $foodSql .= "GROUP BY f.id, f.name, f.picture, f.slug, fgp.id, fgp.color";

            $queries[] = $foodSql;

        }

       /*
        |--------------------------------------------------------------------------
        | DISH QUERY
        |--------------------------------------------------------------------------
        */

        if ($includeDish) {

            $dishSql = "
                    SELECT 
                        d.id,
                        d.name,
                        'Dish' AS item_type,
                        d.picture,
                        d.slug,
                        NULL AS parent_id,
                        NULL AS parent_color,
                        NULL AS unit_measure_ids,
                        NULL AS unit_measure_aliases,
                        GROUP_CONCAT(DISTINCT fgp.id) AS dish_food_group_parent_ids,
                        GROUP_CONCAT(DISTINCT fgp.color) AS dish_food_group_parent_colors
                    FROM dish d
                    LEFT JOIN dish_food_group dfg ON dfg.dish_id = d.id
                    LEFT JOIN food_group fg2 ON fg2.id = dfg.food_group_id
                    LEFT JOIN dish_food_group_parent dfp ON dfp.dish_id = d.id
                    LEFT JOIN food_group_parent fgp ON fgp.id = dfp.food_group_parent_id
                    WHERE 1=1
            ";

            if (!empty($keyword)) {
                $dishSql .= " AND d.name LIKE :keyword ";
            }

            if ($freeGluten) {
                $dishSql .= " AND (d.have_gluten = 0 OR d.have_gluten IS NULL) ";
            }

            if ($freeLactose) {
                $dishSql .= " AND (d.have_lactose = 0 OR d.have_lactose IS NULL) ";
            }

            if (!empty($fglist) && $fglist !== 'none') {
                $dishSql .= " AND fg2.id IN (:fglist_dish) ";
                $params['fglist_dish'] = $fglist;
                $types['fglist_dish']  = ArrayParameterType::INTEGER;
            }

            // if (!empty($dishesNotAllowed)) {
            //     $dishSql .= " AND d.id NOT IN (:dishesNotAllowed_dish) ";
            //     $params['dishesNotAllowed_dish'] = $dishesNotAllowed;
            //     $types['dishesNotAllowed_dish']  = ArrayParameterType::INTEGER;
            // }

            $dishSql .= "GROUP BY d.id, d.name, d.picture, d.slug";

            $queries[] = $dishSql;

        }

        /*
        |--------------------------------------------------------------------------
        | ASSEMBLE UNION
        |--------------------------------------------------------------------------
        */

        if (empty($queries)) {
            return ['data' => [], 'total' => 0];
        }

        $sql = implode(' UNION ALL ', $queries);
        $sql = "SELECT * FROM ( $sql ) AS results";

        /*
        |--------------------------------------------------------------------------
        | COUNT TOTAL (for load more)
        |--------------------------------------------------------------------------
        */

        $countSql = "SELECT COUNT(*) FROM ( $sql ) AS count_results";

        $total = $this->connection
            ->executeQuery($countSql, $params, $types)
            ->fetchOne();

        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */

        $sql .= "
            ORDER BY name ASC
            LIMIT :limit OFFSET :offset
        ";

        $params['limit']  = $limit;
        $params['offset'] = $offset;

        $types['limit']  = \PDO::PARAM_INT;
        $types['offset'] = \PDO::PARAM_INT;

        $data = $this->connection
            ->executeQuery($sql, $params, $types)
            ->fetchAllAssociative();

        return [
            'data'  => $data,
            'total' => (int) $total,
        ];
    }
}