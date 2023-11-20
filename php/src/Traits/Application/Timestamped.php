<?php

namespace Kiniauth\Traits\Application;

/**
 * Injectable trait to integrate standard created date and
 * last modified date into any entity in the system transparently.
 * The functionality is implemented in the Active Record Interceptor
 */
trait Timestamped {

    /**
     * @var \DateTime
     */
    protected $createdDate;

    /**
     * @var \DateTime
     */
    protected $lastModifiedDate;


    /**
     * @return \DateTime
     */
    public function getCreatedDate() {
        return $this->createdDate;
    }

    /**
     * @return \DateTime
     */
    public function getLastModifiedDate() {
        return $this->lastModifiedDate;
    }


}