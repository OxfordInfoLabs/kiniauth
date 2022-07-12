<?php


namespace Kiniauth\Objects\Attachment;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Persistence\ORM\ActiveRecord;


/**
 * Attachment summary for use when summarising attachments.
 *
 * @table ka_attachment
 *
 */
class AttachmentSummary extends ActiveRecord {


    /**
     * Unique id for this attachment
     *
     * @var integer
     */
    protected $id;


    /**
     * Account id for this attachment.
     *
     * @var integer
     */
    protected $accountId;


    /**
     * Project key for this attachment if required
     *
     * @var string
     *
     */
    protected $projectKey;


    /**
     * The type of object which this links back to e.g. Email.
     *
     * @var string
     */
    protected $parentObjectType;

    /**
     * The id of the object which this links back to.
     *
     * @var string
     */
    protected $parentObjectId;


    /**
     * Filename for the attachment
     *
     * @var string
     */
    protected $attachmentFilename;

    /**
     * Mime type for the attachment
     *
     * @var string
     */
    protected $mimeType = "text/plain";


    /**
     * Storage key for this attachment,
     *
     * @var string
     */
    protected $storageKey;


    /**
     * @var \DateTime
     */
    protected $createdDate;

    /**
     * @var \DateTime
     */
    protected $updatedDate;

    // Default attachment storage source
    const DEFAULT_ATTACHMENT_STORAGE = "database";

    /**
     * AttachmentSummary constructor.
     *
     * @param string $attachmentFilename
     * @param string $mimeType
     * @param string $parentObjectType
     * @param string $parentObjectId
     * @param string $storageKey
     * @param string $projectKey
     * @param int $accountId
     */
    public function __construct($attachmentFilename, $mimeType, $parentObjectType, $parentObjectId, $storageKey = null, $projectKey = null, $accountId = null, $id = null) {
        $this->accountId = $accountId;
        $this->projectKey = $projectKey;
        $this->parentObjectType = $parentObjectType;
        $this->parentObjectId = $parentObjectId;
        $this->attachmentFilename = $attachmentFilename;
        $this->mimeType = $mimeType;
        $this->storageKey = $storageKey ?? Configuration::readParameter("attachment.default.storage.key") ?? self::DEFAULT_ATTACHMENT_STORAGE;
        $this->id = $id;
    }


    /**
     * @return int
     */
    public
    function getId() {
        return $this->id;
    }

    /**
     * @return int
     */
    public
    function getAccountId() {
        return $this->accountId;
    }

    /**
     * @return Project
     */
    public
    function getProjectKey() {
        return $this->projectKey;
    }


    /**
     * @return string
     */
    public
    function getParentObjectType() {
        return $this->parentObjectType;
    }

    /**
     * @return int
     */
    public
    function getParentObjectId() {
        return $this->parentObjectId;
    }


    /**
     * @return mixed
     */
    public
    function getAttachmentFilename() {
        return $this->attachmentFilename;
    }

    /**
     * @return string
     */
    public
    function getMimeType() {
        return $this->mimeType;
    }

    /**
     * @return string
     */
    public
    function getStorageKey() {
        return $this->storageKey;
    }

    /**
     * @return \DateTime
     */
    public
    function getCreatedDate() {
        return $this->createdDate;
    }

    /**
     * @return \DateTime
     */
    public
    function getUpdatedDate() {
        return $this->updatedDate;
    }


}
