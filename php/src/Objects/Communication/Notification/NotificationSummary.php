<?php


namespace Kiniauth\Objects\Communication\Notification;


use Kiniauth\Objects\MetaData\Category;
use Kiniauth\Objects\Security\UserCommunicationData;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Class NotificationSummary
 * @package Kiniauth\Objects\Communication\Notification
 *
 * @table ka_notification
 */
class NotificationSummary extends ActiveRecord {

    /**
     * Unique id
     *
     * @var integer
     */
    protected $id;


    /**
     * Created date
     *
     * @var \DateTime
     */
    protected $createdDate;


    /**
     * @var Category
     * @manyToOne
     * @parentJoinColumns category_key,category_account_id,category_project_key
     */
    protected $category;

    /**
     * @var NotificationLevel
     * @manyToOne
     * @parentJoinColumns notification_level_key
     */
    protected $level;

    /**
     * @var string
     */
    protected $title;
    /**
     * @var string
     * @sqlType LONGTEXT
     */
    protected $content;

    /**
     * What state this notification is created in - only applies to internal use
     *
     * @var string
     */
    protected $initialState = self::STATE_UNREAD;


    /**
     * @var NotificationGroupSummary[]
     * @manyToMany
     * @linkTable ka_notification_assigned_group
     */
    protected $notificationGroups;


    /**
     * @var UserCommunicationData
     * @manyToOne
     * @parentJoinColumns user_id
     */
    protected $user;


    /**
     * State constants - may be extended with other states if required
     */
    const STATE_READ = "READ";
    const STATE_UNREAD = "UNREAD";
    const STATE_FLAGGED = "FLAGGED";

    /**
     * NotificationSummary constructor.
     * @param Category $category
     * @param NotificationLevel $level
     * @param string $title
     * @param string $content
     * @param string $initialState
     */
    public function __construct($title, $content,
                                $user = null, $notificationGroups = null,
                                $category = null, $level = null, $initialState = self::STATE_UNREAD, $id = null) {
        $this->title = $title;
        $this->content = $content;
        $this->user = $user;
        $this->notificationGroups = $notificationGroups;
        $this->category = $category;
        $this->level = $level;
        $this->initialState = $initialState;
        $this->createdDate = new \DateTime();
        $this->id = $id;
    }


    /**
     * @return Category
     */
    public function getCategory() {
        return $this->category;
    }


    /**
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * @return NotificationLevel
     */
    public function getLevel() {
        return $this->level;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedDate() {
        return $this->createdDate;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getInitialState() {
        return $this->initialState;
    }


    /**
     * @param \DateTime $createdDate
     */
    public function setCreatedDate($createdDate) {
        $this->createdDate = $createdDate;
    }


    /**
     * @param Category $category
     */
    public function setCategory($category) {
        $this->category = $category;
    }


    /**
     * @param NotificationLevel $level
     */
    public function setLevel($level) {
        $this->level = $level;
    }

    /**
     * @return NotificationGroupSummary[]
     */
    public function getNotificationGroups() {
        return $this->notificationGroups;
    }

    /**
     * @param NotificationGroupSummary[] $notificationGroups
     */
    public function setNotificationGroups($notificationGroups) {
        $this->notificationGroups = $notificationGroups;
    }

    /**
     * @return UserCommunicationData
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param UserCommunicationData $user
     */
    public function setUser($user) {
        $this->user = $user;
    }

    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @param string $content
     */
    public function setContent($content) {
        $this->content = $content;
    }

    /**
     * @param string $initialState
     */
    public function setInitialState($initialState) {
        $this->initialState = $initialState;
    }

    public function getFormattedDate() {
        return $this->createdDate ? $this->createdDate->format("d/m/Y H:i:s") : "";
    }

}
