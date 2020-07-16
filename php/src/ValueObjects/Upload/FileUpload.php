<?php

namespace Kiniauth\ValueObjects\Upload;

/**
 * Basic uploaded file class for use to back the Kiniauth-js file uploader
 *
 * Class UploadedFile
 */
class FileUpload {

    /**
     * @var string
     */
    private $name;


    /**
     * @var int
     */
    private $size;


    /**
     * @var string
     */
    private $type;


    /**
     * Name as returned by server e.g. after an upload process
     *
     * @var string
     */
    private $storedName;


    /**
     * UploadedFile constructor.
     * @param string $name
     * @param int $size
     * @param string $type
     */
    public function __construct($name = null, $size = null, $type = null, $storedName = null) {
        $this->name = $name;
        $this->size = $size;
        $this->type = $type;
        $this->storedName = $storedName;
    }


    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size) {
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getStoredName() {
        return $this->storedName;
    }

    /**
     * @param string $storedName
     */
    public function setStoredName($storedName) {
        $this->storedName = $storedName;
    }


}
