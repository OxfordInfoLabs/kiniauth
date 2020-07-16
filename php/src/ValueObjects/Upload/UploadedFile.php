<?php


namespace Kiniauth\ValueObjects\Upload;


class UploadedFile {

    /**
     * URL To use for direct uploading
     *
     * @var string
     */
    private $uploadUrl;


    /**
     * Filename which may have been modified by process
     *
     * @var string
     */
    private $filename;

    /**
     * UploadedFile constructor.
     *
     * @param string $uploadUrl
     * @param string $filename
     */
    public function __construct($filename, $uploadUrl) {
        $this->uploadUrl = $uploadUrl;
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getUploadUrl() {
        return $this->uploadUrl;
    }

    /**
     * @param string $uploadUrl
     */
    public function setUploadUrl($uploadUrl) {
        $this->uploadUrl = $uploadUrl;
    }

    /**
     * @return string
     */
    public function getFilename() {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename) {
        $this->filename = $filename;
    }


}
