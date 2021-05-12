<?php


namespace Kiniauth\Objects\Account;


use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;


/**
 * Intercept save requests for projects
 *
 */
class ProjectInterceptor extends DefaultORMInterceptor {

    /**
     * @var DatabaseConnection
     */
    private $databaseConnection;

    /**
     * ContactInterceptor constructor.
     *
     * @param DatabaseConnection $databaseConnection
     */
    public function __construct($databaseConnection) {
        $this->databaseConnection = $databaseConnection;
    }


    /**
     * Ensure we have a project number
     *
     * @param Project $object
     */
    public function preSave($object) {



        if (!$object->getNumber() && $object->getAccountId()) {
            $results = $this->databaseConnection->query("SELECT MAX(number) highest FROM ka_project WHERE account_id = ?", $object->getAccountId());
            $highest = $results->fetchAll()[0]["highest"] ?? 0;
            $object->setNumber($highest + 1);
        }
    }


}