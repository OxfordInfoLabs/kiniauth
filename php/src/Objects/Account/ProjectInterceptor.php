<?php


namespace Kiniauth\Objects\Account;


use Kinikit\Core\Util\StringUtils;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;


/**
 * Intercept save requests for projects
 *
 */
class ProjectInterceptor extends DefaultORMInterceptor {

    public function __construct(
        private DatabaseConnection $databaseConnection
    ) {
    }


    /**
     * Ensure we have a project number
     *
     * @param Project $object
     */
    public function preSave($object) {

        if (!$object->getProjectKey() && $object->getAccountId()) {

            $compressedKey = StringUtils::convertToCamelCase($object->getName());
            $proposedKey = $compressedKey;
            $index = 1;
            do {
                $response = $this->databaseConnection->query("SELECT COUNT(*) existing FROM ka_project WHERE account_id = ? AND project_key = ?", $object->getAccountId(), $proposedKey);
                $existing = $response->fetchAll()[0]["existing"];

                if ($existing == 0)
                    break;

                $index++;
                $proposedKey = $compressedKey . $index;

            } while (true);

            $object->setProjectKey($proposedKey);

        }
    }


}