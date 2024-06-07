<?php

namespace Kiniauth\Objects\Account;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ka_account
 * @readOnly
 */
class PublicAccountSummary extends ActiveRecord {

    /**
     * @var int
     * @primaryKey
     * @column account_id
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $logo;


    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getLogo() {
        return $this->logo;
    }


}