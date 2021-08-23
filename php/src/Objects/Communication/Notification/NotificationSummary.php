<?php


namespace Kiniauth\Objects\Communication\Notification;


use Kinikit\Persistence\ORM\ActiveRecord;

class NotificationSummary extends ActiveRecord {

    /**
     * Unique id
     *
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $accountId;


    /**
     * @var integer
     */
    protected $projectId;

    /**
     * Created date
     *
     * @var \DateTime
     */
    protected $createdDate;

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
     * State constants - may be extended with other states if required
     */
    const STATE_READ = "READ";
    const STATE_UNREAD = "UNREAD";
    const STATE_FLAGGED = "FLAGGED";
    /**
     * What state this notification is created in - only applies to internal use
     *
     * @var string
     */
    protected $initialState = self::STATE_UNREAD;


    /**
     * @return int
     */
    public function getAccountId() {
        return $this->accountId;
    }

    /**
     * @return int
     */
    public function getProjectId() {
        return $this->projectId;
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
}