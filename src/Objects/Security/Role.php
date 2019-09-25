<?php


namespace Kiniauth\Objects\Security;
use Kinikit\Persistence\ORM\ActiveRecord;


/**
 * Role class.  A role may contain an array of Privilege objects or may be a standalone role.
 *
 * @table kc_role
 */
class Role extends ActiveRecord {


    /**
     * Auto incremented Id.
     *
     * @var integer
     */
    protected $id;


    /**
     * Optional account id - if this role is a user defined role attached to an account.
     *
     * @var integer
     */
    private $accountId;


    /**
     * Scope of this role.  This is one of the following
     *
     * PARENT_ACCOUNT - Where the role only applies to accounts which have the subAccountsEnabled flag set.
     * ACCOUNT - Where the role applies to any account.
     *
     * @var string
     */
    private $scope = self::SCOPE_ACCOUNT;


    /**
     * String name .
     *
     * @var string
     * @required
     */
    private $name;


    /**
     * Description for this role.
     *
     * @var string
     * @required
     */
    private $description;


    /**
     * An array of privileges.
     *
     * @json
     * @var string[]
     * @sqlType LONGTEXT
     */
    private $privileges;


    // SCOPE CONSTANTS
    const SCOPE_PARENT_ACCOUNT = "PARENT_ACCOUNT";
    const SCOPE_ACCOUNT = "ACCOUNT";

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
     * @return string
     */
    public function getScope() {
        return $this->scope;
    }

    /**
     * @param string $scope
     */
    public function setScope($scope) {
        $this->scope = $scope;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
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

    /**
     * @return string[]
     */
    public function getPrivileges() {
        return $this->privileges;
    }

    /**
     * @param string[] $privileges
     */
    public function setPrivileges($privileges) {
        $this->privileges = $privileges;
    }


}
