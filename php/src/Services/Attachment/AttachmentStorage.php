<?php


namespace Kiniauth\Services\Attachment;


use Kinikit\Core\Stream\ReadableStream;
use Kinikit\Core\Stream\String\ReadOnlyStringStream;

/**
 * Generic attachment storage interface for storing and retrieving/streaming attachment content.
 *
 * Interface AttachmentStorage
 * @package Kiniauth\Objects\Attachment
 *
 * @implementation database Kiniauth\Services\Attachment\Storage\DatabaseAttachmentStorage
 * @implementation file Kiniauth\Services\Attachment\Storage\FileAttachmentStorage
 *
 */
abstract class AttachmentStorage {


    /**
     * Save attachment content supplied as a stream.  This is called just before the save of the
     * Attachment object itself.
     *
     * @param $attachment
     * @param ReadableStream $contentStream
     * @return mixed
     */
    public abstract function saveAttachmentContent($attachment, $contentStream);


    /**
     * Delete attachment content - called just before the obejct is deleted
     *
     * @param $attachment
     * @return mixed
     */
    public abstract function removeAttachmentContent($attachment);


    /**
     * Get the content for an attachment as a string
     *
     * @param $attachment
     * @return string
     */
    public abstract function getAttachmentContent($attachment);


    /**
     * Stream attachment content.
     *
     * @param $attachment
     * @return ReadableStream
     */
    public function streamAttachmentContent($attachment) {
        return new ReadOnlyStringStream($this->getAttachmentContent($attachment));
    }

}