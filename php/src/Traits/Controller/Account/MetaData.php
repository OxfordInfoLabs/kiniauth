<?php


namespace Kiniauth\Traits\Controller\Account;


use Kiniauth\Services\MetaData\MetaDataService;

trait MetaData {

    /**
     * @var MetaDataService
     */
    private $metaDataService;

    /**
     * MetaData constructor.
     *
     * @param MetaDataService $metaDataService
     */
    public function __construct($metaDataService) {
        $this->metaDataService = $metaDataService;
    }


    /**
     * Get Account level tags
     *
     * @http GET /
     */
    public function getTags() {
        return $this->metaDataService->getAvailableTags();
    }





}