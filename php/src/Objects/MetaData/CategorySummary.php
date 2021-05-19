<?php


namespace Kiniauth\Objects\MetaData;


use Kinikit\Persistence\ORM\ActiveRecord;

class CategorySummary extends ActiveRecord {

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     * @required
     */
    protected $category;

    /**
     * @var string
     */
    protected $description;


    /**
     * TagSummary constructor.
     *
     * @param string $category
     * @param string $description
     * @param string $key
     */
    public function __construct($category, $description = null, $key = null) {
        $this->category = $category;
        $this->description = $description;
        $this->key = $key;
    }


    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getCategory() {
        return $this->category;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param string $category
     */
    public function setCategory($category) {
        $this->category = $category;
    }

    /**
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }




}