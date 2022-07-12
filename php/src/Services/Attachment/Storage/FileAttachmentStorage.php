<?php


namespace Kiniauth\Services\Attachment\Storage;


use Kiniauth\Objects\Attachment\Attachment;
use Kiniauth\Services\Attachment\AttachmentStorage;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Stream\File\ReadOnlyFileStream;
use League\Flysystem\Config;


/**
 * Class FileAttachmentStorage
 * @package Kiniauth\Services\Attachment\Storage
 */
class FileAttachmentStorage extends AttachmentStorage {

    /**
     * Save attachment content to file
     *
     * @param Attachment $attachment
     * @param \Kinikit\Core\Stream\ReadableStream $contentStream
     */
    public function saveAttachmentContent($attachment, $contentStream) {
        $targetFilename = $this->getTargetFilename($attachment);
        $targetFile = fopen($targetFilename, "w");
        while (!$contentStream->isEof()) {
            $line = $contentStream->read(1024);
            fwrite($targetFile, $line);
        }
        $contentStream->close();
        fclose($targetFile);
    }

    /**
     * @param Attachment $attachment
     */
    public function removeAttachmentContent($attachment) {
        $targetFilename = $this->getTargetFilename($attachment);
        if (file_exists($targetFilename)) {
            unlink($targetFilename);
        }
    }

    /**
     * Get attachment content
     *
     * @param $attachment
     * @return string|void
     */
    public function getAttachmentContent($attachment) {
        return $this->streamAttachmentContent($attachment)->getContents();
    }

    /**
     * Stream attachment content
     *
     * @param $attachment
     * @return \Kinikit\Core\Stream\ReadableStream|\Kinikit\Core\Stream\String\ReadOnlyStringStream
     */
    public function streamAttachmentContent($attachment) {
        return new ReadOnlyFileStream($this->getTargetFilename($attachment));
    }


    /**
     * Get target filename for file
     *
     * @param Attachment $attachment
     */
    private function getTargetFilename($attachment) {

        $path = rtrim(Configuration::readParameter("file.attachment.storage.root"), "/");

        if ($attachment->getAccountId()) {
            $path .= "/Account/" . $attachment->getAccountId();
            if ($attachment->getProjectKey()) {
                $path .= "/" . $attachment->getProjectKey();
            }
        } else {
            $path .= "/Admin";
        }

        $path .= "/" . $attachment->getParentObjectType() . "/" . $attachment->getParentObjectId() . "/";
        $filename = $attachment->getId() . "-" . $attachment->getAttachmentFilename();

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }


        return $path . $filename;
    }

}