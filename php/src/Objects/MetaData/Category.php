<?php


namespace Kiniauth\Objects\MetaData;


use Kiniauth\Traits\Account\AccountProject;

/**
 *
 * Main category entity for organising system objects
 *
 * @table ka_category
 * @generate
 * @interceptor \Kiniauth\Objects\MetaData\CategoryInterceptor
 */
class Category extends CategorySummary {

    use AccountProject;

    /**
     * @var string
     * @primaryKey
     */
    protected $key;

    /**
     * @var integer
     * @primaryKey
     */
    protected $accountId;


    /**
     * @var string
     * @primaryKey
     */
    protected $projectKey;


    /**
     * @var string
     */
    protected $parentKey;


    /**
     * @var integer
     */
    protected $parentAccountId;


    /**
     * @var string
     */
    protected $parentProjectkey;


    /**
     * Category constructor.
     *
     * @param CategorySummary $categorySummary
     * @param string $tag
     * @param string $description
     * @param Category $parentCategory
     */
    public function __construct($categorySummary = null, $accountId = null, $projectKey = null, $parentCategory = null) {
        if ($categorySummary instanceof CategorySummary) {
            parent::__construct($categorySummary->getCategory(), $categorySummary->getDescription(), $categorySummary->getKey());
        }
        $this->accountId = $accountId ?? -1;
        $this->projectKey = $projectKey ?? "";

        if ($parentCategory) {
            $this->parentKey = $parentCategory->getKey();
            $this->parentAccountId = $parentCategory->getAccountId();
            $this->parentProjectkey = $parentCategory->getProjectKey();
        }
    }


    /**
     * @param string $key
     */
    public function setKey($key) {
        $this->key = $key;
    }


}