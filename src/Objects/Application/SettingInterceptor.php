<?php


namespace Kiniauth\Objects\Application;


use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;

/**
 *
 * @noGenerate
 *
 */
class SettingInterceptor extends DefaultORMInterceptor {


    /**
     * Attach the definition to a setting object
     *
     * @param Setting $object
     * @param null $upfInstance
     * @return bool
     */
    public function postMap($object = null) {

        // Ensure we populate this setting with a definition.
        $object->populateSettingWithDefinition();

        return true;
    }


}
