<?php


namespace Kiniauth\Objects\Communication\Attachment;

/**
 *
 * @table ka_attachment
 */
class Attachment extends AttachmentSummary {

    /**
     * The raw content of this attachment.
     *
     * @var string
     * @sqlType LONGTEXT
     */
    private $content;


    public function __construct($parentObjectType = null, $parentObjectId = null, $localFilePath = null, $attachmentFilename = null, $accountId = null) {

        $this->parentObjectType = $parentObjectType;
        $this->parentObjectId = $parentObjectId;
        if ($localFilePath) {
            // Split the file
            $explodedFile = explode("/", $localFilePath);

            $this->attachmentFilename = $attachmentFilename ? $attachmentFilename : array_pop($explodedFile);
            $this->mimeType = mime_content_type($localFilePath);
            $this->content = file_get_contents($localFilePath);
        } else {
            $this->attachmentFilename = $attachmentFilename;
        }
        $this->accountId = $accountId;

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


}
