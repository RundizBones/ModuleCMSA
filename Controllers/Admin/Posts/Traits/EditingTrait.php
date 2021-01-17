<?php


namespace Rdb\Modules\RdbCMSA\Controllers\Admin\Posts\Traits;


/**
 * Editing trait for Editing controller.
 */
trait EditingTrait
{


    /**
     * @var \Rdb\Modules\RdbCMSA\Models\PostsDb $PostsDb 
     */
    protected $PostsDb;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        parent::__construct($Container);

        $this->PostsDb = new \Rdb\Modules\RdbCMSA\Models\PostsDb($Container);
    }// __construct


    /**
     * Get related data such as categories, statuses.
     * 
     * @return array Return array with `categories`, `postStatuses` keys.
     */
    protected function getRelatedData(): array
    {
        $output = [];

        // get categories
        $output = array_merge($output, $this->getCategories());

        // get post statuses. 
        $output = array_merge($output, $this->getStatuses());

        return $output;
    }// getRelatedData


    /**
     * Get categories.
     * 
     * @return array Return array with `categories` key.
     */
    protected function getCategories(): array
    {
        $output = [];

        // get categories. ------------------------------------
        $CategoriesDb = new \Rdb\Modules\RdbCMSA\Models\CategoriesDb($this->Db->PDO(), $this->Container);
        $options = [];
        $options['unlimited'] = true;
        $options['where'] = [
            'whereString' => '`parent`.`t_type` = :t_type' . (isset($options['search']) ? ' AND `child`.`t_type` = :t_type' : ''),
            'whereValues' => [':t_type' => $this->Input->get('t_type', 'category', FILTER_SANITIZE_STRING)],
        ];
        $result = $CategoriesDb->listTaxonomyFlatten($options);
        unset($CategoriesDb, $options);
        $output['categories'] = [
            'total' => ($result['total'] ?? 0),
            'items' => ($result['items'] ?? []),
        ];
        unset($result);
        // end get categories. ------------------------------------

        return $output;
    }// getCategories


    /**
     * Get statuses.
     * 
     * @return array Return array with `postStatuses` key.
     */
    protected function getStatuses(): array
    {
        $output = [];

        // get post statuses. --------------------------------------
        $statuses = $this->PostsDb->postStatuses;
        $output['postStatuses'] = [];
        if (is_array($statuses)) {
            foreach ($statuses as $key => $rawMsg) {
                if ($key === 5 || $key === 6) {
                    continue;
                }

                $output['postStatuses'][] = [
                    'value' => $key,
                    'text' => d__('rdbcmsa', $rawMsg),
                ];
            }// endforeach;
            unset($key, $rawMsg);
        }
        unset($statuses);
        // end get post statuses. ---------------------------------

        return $output;
    }// getStatuses


}
