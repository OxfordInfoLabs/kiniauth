<?php


namespace Kiniauth\Objects\Security;


use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;

class UserInterceptor extends DefaultORMInterceptor {

    /**
     * After save, update user password history
     *
     * @param $object
     */
    public function postSave($object) {
        (new UserPasswordHistory($object->getId(), $object->getHashedPassword()))->save();
    }
}