<?php


namespace Kiniauth\Objects\Account;


use Kinikit\Persistence\ORM\ActiveRecord;


class AccountCSVProfileSummary extends ActiveRecord {

    /**
     * Array of fields to map to
     *
     * @var array
     * @json
     */
    protected $mapping;

    /**
     * AccountCSVProfileSummary constructor.
     *
     * @param array $mapping
     */
    public function __construct(array $mapping) {
        $this->mapping = $mapping;
    }

    /**
     * @return array
     */
    public function getMapping(): array {
        return $this->mapping;
    }

    /**
     * @param array $mapping
     */
    public function setMapping(array $mapping): void {
        $this->mapping = $mapping;
    }

}