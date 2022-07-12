<?php


namespace Kiniauth\Objects\Attachment;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Stream\Stream;

/**
 *
 * @table ka_attachment
 * @generate
 */
class Attachment extends AttachmentSummary {

    /**
     * The raw content of this attachment.
     *
     * @var string
     * @sqlType LONGTEXT
     */
    private $content;


    /**
     * Attachment constructor.
     * @param AttachmentSummary $attachmentSummary
     */
    public function __construct($attachmentSummary) {
        if ($attachmentSummary) {
            parent::__construct($attachmentSummary->getAttachmentFilename(), $attachmentSummary->getMimeType(), $attachmentSummary->getParentObjectType(), $attachmentSummary->getParentObjectId(), $attachmentSummary->getStorageKey(), $attachmentSummary->getProjectKey(), $attachmentSummary->getAccountId(), $attachmentSummary->getId());
            $this->createdDate = $attachmentSummary->getCreatedDate();
            $this->updatedDate = $attachmentSummary->getUpdatedDate();
        }
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @param string $parentObjectType
     */
    public function setParentObjectType($parentObjectType) {
        $this->parentObjectType = $parentObjectType;
    }

    /**
     * @param int $parentObjectId
     */
    public function setParentObjectId($parentObjectId) {
        $this->parentObjectId = $parentObjectId;
    }

    /**
     * @param string $attachmentFilename
     */
    public function setAttachmentFilename($attachmentFilename) {
        $this->attachmentFilename = $attachmentFilename;
    }

    /**
     * @param string $mimeType
     */
    public function setMimeType($mimeType) {
        $this->mimeType = $mimeType;
    }

    /**
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content) {
        $this->content = $content;
    }

    /**
     * @param Project $projectKey
     */
    public function setProjectKey($projectKey) {
        $this->projectKey = $projectKey;
    }

    /**
     * @param string $storageKey
     */
    public function setStorageKey($storageKey) {
        $this->storageKey = $storageKey;
    }

    /**
     * @param \DateTime $updatedDate
     */
    public function setUpdatedDate($updatedDate) {
        $this->updatedDate = $updatedDate;
    }

    /**
     * Save method
     */
    public function save() {

        // Sync dates
        if (!$this->createdDate)
            $this->createdDate = new \DateTime();
        $this->updatedDate = new \DateTime();

        parent::save();
    }


}
