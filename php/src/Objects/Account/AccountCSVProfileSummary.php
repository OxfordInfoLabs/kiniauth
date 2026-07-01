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
     * Array of fields to be included in extra_data
     *
     * @var array
     * @json
     */
    protected $extraDataFlags = [];

    /**
     * AccountCSVProfileSummary constructor.
     *
     * @param array $mapping
     * @param array $extraDataFlags
     * @param ?int $id
     */
    public function __construct($mapping, $extraDataFlags, $id = null) {
        $this->mapping = $mapping;
        $this->extraDataFlags = $extraDataFlags;
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

    /**
     * @return array
     */
    public function getExtraDataFlags(): array {
        return $this->extraDataFlags;
    }

    /**
     * @param array $extraDataFlags
     */
    public function setExtraDataFlags(array $extraDataFlags): void {
        $this->extraDataFlags = $extraDataFlags;
    }

}