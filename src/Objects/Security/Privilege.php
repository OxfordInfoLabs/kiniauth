<?php


namespace Kiniauth\Objects\Security;

use Kinikit\Core\Object\SerialisableObject;


/**
 * Privilege class which encodes a simple key and description for a system privilege.
 */
class Privilege extends SerialisableObject {

    /**
     * A string identifier for a privilege for programmatic use.
     *
     * @var string
     * @validation required
     */
    private $key;


    /**
     * A full description for this privilege.
     *
     * @var string
     * @validation required
     */
    private $description;


    /**
     * The scope for this privilege
     *
     * @var string
     */
    private $scope = Role::SCOPE_ACCOUNT;


    // Built in privileges.
    const PRIVILEGE_ACCESS = "access";

    /**
     * Privilege constructor.
     * @param string $key
     * @param string $description
     * @param string $scope
     */
    public function __construct($key = null, $description = null, $scope = null) {
        $this->key = $key;
        $this->description = $description;
        $this->scope = $scope;
    }


    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key) {
        $this->key = $key;
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


}
