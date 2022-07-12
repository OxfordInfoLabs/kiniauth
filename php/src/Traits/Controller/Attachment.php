<?php


namespace Kiniauth\Traits\Controller;

use Kiniauth\Services\Attachment\AttachmentService;


trait Attachment {

    /**
     * @var AttachmentService
     */
    private $attachmentService;

    /**
     * Attachment constructor.
     *
     * @param AttachmentService $attachmentService
     */
    public function __construct($attachmentService) {
        $this->attachmentService = $attachmentService;
    }

    /**
     * @http GET /$id
     *
     * @param $id
     */
    public function streamAttachment($id) {

        // Get the attachment stream
        return $this->attachmentService->streamAttachment($id, true);

    }

}