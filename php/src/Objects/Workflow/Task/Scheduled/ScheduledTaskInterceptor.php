<?php


namespace Kiniauth\Objects\Workflow\Task\Scheduled;


use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;
use Kinikit\Persistence\ORM\Interceptor\ORMInterceptor;

class ScheduledTaskInterceptor extends DefaultORMInterceptor {


    /**
     * @var bool
     */
    public static $disabled = false;

    /**
     * Pre-save scheduled tasks
     *
     * @param ScheduledTask $object
     */
    public function preSave($object) {
        if (!self::$disabled && $object->getStatus() != ScheduledTask::STATUS_RUNNING)
            $object->recalculateNextStartTime();
    }

}