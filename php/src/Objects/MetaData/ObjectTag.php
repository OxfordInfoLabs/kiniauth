<?php


namespace Kiniauth\Objects\MetaData;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Encode a relationship between an object and a tag.  This is designed for
 * generic use in relationships of concrete entities as required
 *
 * @table ka_object_tag
 * @generate
 */
class ObjectTag {

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
     * @maxLength 100
     * @primaryKey
     */
    private $objectId;


    /**
     * @var string
     * @primaryKey
     */
    private $tagKey = "";

    /**
     * @var integer
     * @primaryKey
     */
    private $tagAccountId = "";


    /**
     * @var string
     * @maxLength 50
     * @primaryKey
     */
    private $tagProjectKey = "";

    /**
     * @manyToOne
     * @parentJoinColumns tag_key,[tag_account_id],[tag_project_key]
     *
     * @var Tag
     */
    private $tag;


    /**
     * ObjectTag constructor.
     *
     * @param Tag $tag
     * @param string $objectType
     * @param string $objectId
     *
     */
    public function __construct($tag, $objectType = null, $objectId = null) {
        $this->objectType = $objectType;
        $this->objectId = $objectId;
        $this->tag = $tag;
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
     * @return int
     */
    public function getObjectId() {
        return $this->objectId;
    }

    /**
     * @param int $objectId
     */
    public function setObjectId($objectId) {
        $this->objectId = $objectId;
    }

    /**
     * @return Tag
     */
    public function getTag() {
        return $this->tag;
    }

    /**
     * @param Tag $tag
     */
    public function setTag($tag) {
        $this->tag = $tag;
    }


}