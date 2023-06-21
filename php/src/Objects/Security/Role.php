<?php


namespace Kiniauth\Objects\Security;

use Kinikit\Persistence\ORM\ActiveRecord;


/**
 * Role class.  A role may contain an array of Privilege objects or may be a standalone role.
 *
 * @table ka_role
 * @generate
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
    private $definedAccountId;


    /**
     * Scope of this role.  The following are built in scopes but can be extended with others
     *
     * PARENT_ACCOUNT - Where the role only applies to accounts which have the subAccountsEnabled flag set.
     * ACCOUNT - Where the role applies to any account.
     * PROJECT - Where the role applies to a project
     *
     * @var string
     */
    private $scope;


    /**
     * @var string
     */
    private $appliesTo;


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
    const SCOPE_PROJECT = "PROJECT";

    // APPLIES TO CONSTANTS
    const APPLIES_TO_USER = "USER";
    const APPLIES_TO_API_KEY = "API_KEY";
    const APPLIES_TO_ALL = "ALL";

    /**
     * Role constructor.
     *
     * @param string $scope
     * @param $appliesTo
     * @param string $name
     * @param string $description
     * @param string[] $privileges
     */
    public function __construct($scope, $appliesTo, $name, $description, $privileges, $id = null) {
        $this->scope = $scope ?? self::SCOPE_ACCOUNT;
        $this->appliesTo = $appliesTo ?? self::APPLIES_TO_USER;
        $this->name = $name;
        $this->description = $description;
        $this->privileges = $privileges;
        $this->id = $id;

    }


    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }


    /**
     * @return int
     */
    public function getDefinedAccountId() {
        return $this->definedAccountId;
    }

    /**
     * @param int $definedAccountId
     */
    public function setDefinedAccountId($definedAccountId) {
        $this->definedAccountId = $definedAccountId;
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
    public function getAppliesTo() {
        return $this->appliesTo;
    }

    /**
     * @param string $appliesTo
     */
    public function setAppliesTo($appliesTo) {
        $this->appliesTo = $appliesTo;
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
