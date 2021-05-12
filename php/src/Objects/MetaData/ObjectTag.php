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
     * @primaryKey
     */
    private $objectType;

    /**
     * Assume related object has integer id
     *
     * @var integer
     * @primaryKey
     */
    private $objectId;


    /**
     * @manyToOne
     * @parentJoinColumns tag_id
     *
     * @var Tag
     */
    private $tag;

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