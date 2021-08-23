<?php


namespace Kiniauth\Objects\Communication\Notification;

use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class NotificationLevel
 *
 * @package Kiniauth\Objects\Communication\Notification
 *
 * @table ka_notification_level
 * @generate
 */
class NotificationLevel extends ActiveRecord {

    /**
     * @var string
     * @primaryKey
     */
    private $key;

    /**
     * @var string
     */
    private $title;

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
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }


}