<?php


namespace Kiniauth\Services\Attachment\Storage;


use Kiniauth\Objects\Attachment\Attachment;
use Kiniauth\Services\Attachment\AttachmentStorage;
use Kinikit\Core\Stream\ReadableStream;


class DatabaseAttachmentStorage extends AttachmentStorage {

    /**
     * Simply update object directly with stream contents
     *
     * @param Attachment $attachment
     * @param ReadableStream $contentStream
     */
    public function saveAttachmentContent($attachment, $contentStream) {
        $attachment->setContent($contentStream->getContents());
    }

    /**
     * @param Attachment $attachment
     */
    public function removeAttachmentContent($attachment) {
        $attachment->setContent(null);
    }

    /**
     * @param Attachment $attachment
     * @return string
     */
    public function getAttachmentContent($attachment) {
        return $attachment->getContent();
    }
}