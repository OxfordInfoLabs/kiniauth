<?php


namespace Kiniauth\Objects\MetaData;


use Kinikit\Persistence\ORM\ActiveRecord;

class TagSummary extends ActiveRecord {

    /**
     * @var string
     * @primaryKey
     */
    protected $key;

    /**
     * @var string
     * @required
     */
    protected $tag;

    /**
     * @var string
     */
    protected $description;

    /**
     * TagSummary constructor.
     * @param string $tag
     * @param string $description
     */
    public function __construct($tag, $description = null, $key = null) {
        $this->tag = $tag;
        $this->description = $description;
        $this->key = $key;
    }


    /**
     * @return int
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getTag() {
        return $this->tag;
    }

    /**
     * @param string $tag
     */
    public function setTag($tag) {
        $this->tag = $tag;
    }


    /**
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }
}