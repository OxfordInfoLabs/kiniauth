<?php


namespace Kiniauth\Objects\MetaData;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Generic structured data
 *
 * @table ka_object_structured_data
 * @generate
 */
class ObjectStructuredData extends ActiveRecord {

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
     * Type of data being written
     *
     * @var string
     * @maxLength 50
     * @primaryKey
     */
    private $dataType;


    /**
     * Primary key for the data item being written
     *
     * @var string
     * @primaryKey
     */
    private $primaryKey;


    /**
     * Store the data as a large JSON block
     *
     * @var mixed
     * @json
     * @sqlType LONGTEXT
     */
    private $data;

    /**
     * ObjectStructuredData constructor.
     *
     * @param string $objectType
     * @param string $objectId
     * @param string $dataType
     * @param string $primaryKey
     * @param string $data
     */
    public function __construct($objectType, $objectId, $dataType, $primaryKey, $data) {
        $this->objectType = $objectType;
        $this->objectId = $objectId;
        $this->dataType = $dataType;
        $this->primaryKey = $primaryKey;
        $this->data = $data;
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
    public function getDataType() {
        return $this->dataType;
    }

    /**
     * @param string $dataType
     */
    public function setDataType($dataType) {
        $this->dataType = $dataType;
    }

    /**
     * @return string
     */
    public function getPrimaryKey() {
        return $this->primaryKey;
    }

    /**
     * @param string $primaryKey
     */
    public function setPrimaryKey($primaryKey) {
        $this->primaryKey = $primaryKey;
    }

    /**
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data) {
        $this->data = $data;
    }

    /**
     * Return the replace key
     */
    public function getReplaceKey() {
        return $this->getObjectType() . "||" . $this->getObjectId() . "||" . $this->getDataType();
    }

}