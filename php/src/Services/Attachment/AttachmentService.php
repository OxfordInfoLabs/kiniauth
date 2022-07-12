<?php


namespace Kiniauth\Services\Attachment;

use Kiniauth\Objects\Attachment\Attachment;
use Kiniauth\Objects\Attachment\AttachmentSummary;
use Kiniauth\Services\Attachment\Storage\DatabaseAttachmentStorage;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Stream\ReadableStream;
use Kinikit\Core\Stream\Stream;
use Kinikit\MVC\ContentSource\ReadableStreamContentSource;
use Kinikit\MVC\Response\Download;

/**
 * Attachment service for saving and retrieving attachments
 *
 * Class AttachmentService
 * @package Kiniauth\Services\Attachment
 */
class AttachmentService {


    /**
     * Stream an attachment returning a stream object
     *
     * @param integer $attachmentId
     * @return ReadableStream|Download
     */
    public function streamAttachment($attachmentId, $asDownload = false) {

        /**
         * @var Attachment $attachment
         */
        $attachment = Attachment::fetch($attachmentId);

        /**
         * @var AttachmentStorage $storage
         */
        $storage = Container::instance()->getInterfaceImplementation(AttachmentStorage::class, $attachment->getStorageKey());

        // Return the stream
        $stream = $storage->streamAttachmentContent($attachment);

        return $asDownload ? new Download(new ReadableStreamContentSource($stream, $attachment->getMimeType()), $attachment->getAttachmentFilename()) : $stream;

    }


    /**
     * Save an attachment object from an attachment summary and content stream
     *
     * @param AttachmentSummary attachmentSummary
     * @param Stream $contentStream
     * @return integer
     */
    public function saveAttachment($attachmentSummary, $contentStream) {

        // Create a new attachment object
        $attachment = new Attachment($attachmentSummary);

        /**
         * @var AttachmentStorage $storage
         */
        $storage = Container::instance()->getInterfaceImplementation(AttachmentStorage::class, $attachment->getStorageKey());

        // If not database attachment save first so we have an id.
        if (!($storage instanceof DatabaseAttachmentStorage))
            $attachment->save();


        // Save the content
        $storage->saveAttachmentContent($attachment, $contentStream);

        // If database attachment storage save now.
        if ($storage instanceof DatabaseAttachmentStorage)
            $attachment->save();

        // Get the id
        return $attachment->getId();


    }


    /**
     * Remove an attachment
     *
     * @param $attachmentId
     */
    public function removeAttachment($attachmentId) {

        /**
         * @var Attachment $attachment
         */
        $attachment = Attachment::fetch($attachmentId);

        /**
         * @var AttachmentStorage $storage
         */
        $storage = Container::instance()->getInterfaceImplementation(AttachmentStorage::class, $attachment->getStorageKey());

        // Remove content
        $storage->removeAttachmentContent($attachment);

        // Delete attachment
        $attachment->remove();

    }

}
