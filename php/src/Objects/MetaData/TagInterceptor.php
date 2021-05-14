<?php


namespace Kiniauth\Objects\MetaData;


use Kinikit\Core\Util\StringUtils;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;

/**
 * Intercept save requests for projects
 *
 */
class TagInterceptor extends DefaultORMInterceptor {

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
     * @param Tag $object
     */
    public function preSave($object) {

        if (!$object->getKey()) {

            $compressedKey = StringUtils::convertToCamelCase($object->getTag());
            $proposedKey = $compressedKey;
            $index = 1;
            do {

                $clause = "key = ?";
                $params = [$proposedKey];

                if ($object->getAccountId()) {
                    $clause .= " AND (account_id IS NULL or account_id = ?)";
                    $params[] = $object->getAccountId();
                }

                if ($object->getProjectKey()) {
                    $clause .= " AND (project_key IS NULL or project_key = ?)";
                    $params[] = $object->getProjectKey();
                }


                $response = $this->databaseConnection->query("SELECT COUNT(*) existing FROM ka_tag 
                WHERE $clause", $params);
                $existing = $response->fetchAll()[0]["existing"];

                if ($existing == 0)
                    break;

                $index++;
                $proposedKey = $compressedKey . $index;

            } while (true);

            $object->setKey($proposedKey);

        }
    }


}