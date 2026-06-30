<?php


namespace Kiniauth\Objects\Account;


use Kinikit\Persistence\ORM\ActiveRecord;


class AccountCSVProfileSummary extends ActiveRecord {

    /**
     * Unique primary key
     *
     * @autoIncrement
     */
    protected $id;

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
     * @param ?int $id
     */
    public function __construct($mapping, $id = null) {
        $this->mapping = $mapping;
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId(): ?int {
        return $this->id;
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