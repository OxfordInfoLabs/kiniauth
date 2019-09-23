<?php


namespace Kiniauth\Objects\Application;


use Kinikit\Persistence\UPF\Framework\UPFObjectInterceptorBase;

class SettingInterceptor extends UPFObjectInterceptorBase {


    /**
     * Attach the definition to a setting object
     *
     * @param Setting $object
     * @param null $upfInstance
     * @return bool
     */
    public function postMap($object = null, $upfInstance = null) {

        // Ensure we populate this setting with a definition.
        $object->populateSettingWithDefinition();

        return true;
    }


}
