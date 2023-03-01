<?php


namespace Kiniauth\Objects\MetaData;

/**
 * Encode a relationship between an object and a category.  This is designed for
 * generic use in relationships of concrete entities as required
 *
 * @table ka_object_category
 * @generate
 */
class ObjectCategory {

    /**
     * @var string
     * @maxLength 50
     * @primaryKey
     */
    private $objectType;

    /**
     * Assume related object has single pk
     *
     * @var string
     * @primaryKey
     * @maxLength 100
     */
    private $objectId;


    /**
     * @var string
     * @primaryKey
     */
    private $categoryKey = "";

    /**
     * @var integer
     * @primaryKey
     */
    private $categoryAccountId = "";


    /**
     * @var string
     * @primaryKey
     * @maxLength 50
     */
    private $categoryProjectKey = "";

    /**
     * @manyToOne
     * @parentJoinColumns category_key,[category_account_id],[category_project_key]
     *
     * @var Category
     */
    private $category;


    /**
     * ObjectCategory constructor.
     *
     * @param Category $category
     * @param string $objectType
     * @param string $objectId
     *
     */
    public function __construct($category, $objectType = null, $objectId = null) {
        $this->objectType = $objectType;
        $this->objectId = $objectId;
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getObjectType() {
        return $this->objectType;
    }

    /**
     * @param string $objectType
     */
    public function setObjectType($objectType) {
        $this->objectType = $objectType;
    }

    /**
     * @return string
     */
    public function getObjectId() {
        return $this->objectId;
    }

    /**
     * @param string $objectId
     */
    public function setObjectId($objectId) {
        $this->objectId = $objectId;
    }

    /**
     * @return string
     */
    public function getCategoryKey() {
        return $this->categoryKey;
    }

    /**
     * @param string $categoryKey
     */
    public function setCategoryKey($categoryKey) {
        $this->categoryKey = $categoryKey;
    }

    /**
     * @return int
     */
    public function getCategoryAccountId() {
        return $this->categoryAccountId;
    }

    /**
     * @param int $categoryAccountId
     */
    public function setCategoryAccountId($categoryAccountId) {
        $this->categoryAccountId = $categoryAccountId;
    }

    /**
     * @return string
     */
    public function getCategoryProjectKey() {
        return $this->categoryProjectKey;
    }

    /**
     * @param string $categoryProjectKey
     */
    public function setCategoryProjectKey($categoryProjectKey) {
        $this->categoryProjectKey = $categoryProjectKey;
    }

    /**
     * @return Category
     */
    public function getCategory() {
        return $this->category;
    }

    /**
     * @param Category $category
     */
    public function setCategory($category) {
        $this->category = $category;
    }


}