<?php


namespace Kiniauth\Objects\Workflow\Task\Scheduled;


use Kinikit\Persistence\ORM\Interceptor\DefaultORMInterceptor;

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

        if (!self::$disabled && $object->getStatus() != ScheduledTask::STATUS_RUNNING) {

            // Read current stored time.  If the next start time has been updated
            // Do not recalculate
            if ($object->getId()) {
                $savedVersion = ScheduledTask::fetch($object->getId());

                if ($savedVersion->getNextStartTime() != $object->getNextStartTime()) {
                    $object->setNextStartTime($savedVersion->getNextStartTime());
                    return;
                }
            }


            $object->recalculateNextStartTime();
        }
    }

}