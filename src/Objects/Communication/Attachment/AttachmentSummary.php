<?php


namespace Kiniauth\Objects\Communication\Attachment;
use Kinikit\Persistence\ORM\ActiveRecord;


/**
 * Attachment summary for use when summarising attachments.
 *
 * @table ka_attachment
 * @noGenerate
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
     * The type of object which this links back to e.g. Email.
     *
     * @var string
     */
    protected $parentObjectType;

    /**
     * The id of the object which this links back to.
     *
     * @var integer
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
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getAccountId() {
        return $this->accountId;
    }

    /**
     * @return string
     */
    public function getParentObjectType() {
        return $this->parentObjectType;
    }

    /**
     * @return int
     */
    public function getParentObjectId() {
        return $this->parentObjectId;
    }


    /**
     * @return mixed
     */
    public function getAttachmentFilename() {
        return $this->attachmentFilename;
    }

    /**
     * @return string
     */
    public function getMimeType() {
        return $this->mimeType;
    }


}
