<?php


namespace Kiniauth\Objects\Workflow;


use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;

/**
 * Class PendingActionInterceptor
 * @package Kiniauth\Objects\Workflow
 */
class PendingActionInterceptor extends DefaultORMInterceptor {


    /**
     * Check to see if actions have expired.
     *
     * @param PendingAction $object
     * @return bool
     */
    public function postMap($object) {
        if ($object->getExpiryDateTime() < new \DateTime()) {
            $object->remove();
            return false;
        }
        return true;
    }


}
