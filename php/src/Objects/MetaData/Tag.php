<?php


namespace Kiniauth\Objects\MetaData;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_tag
 * @generate
 */
class Tag extends ActiveRecord {

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $accountId;


    /**
     * Project number within the account
     *
     * @var integer
     */
    protected $projectNumber;

    /**
     * @var string
     */
    private $tag;

    /**
     * @var string
     */
    private $description;

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getAccountId() {
        return $this->accountId;
    }

    /**
     * @param int $accountId
     */
    public function setAccountId($accountId) {
        $this->accountId = $accountId;
    }

    /**
     * @return int
     */
    public function getProjectNumber() {
        return $this->projectNumber;
    }

    /**
     * @param int $projectNumber
     */
    public function setProjectNumber($projectNumber) {
        $this->projectNumber = $projectNumber;
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
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }


}