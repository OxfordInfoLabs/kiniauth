<?php


namespace Kiniauth\Traits\Controller\Account;


use Kiniauth\Objects\MetaData\TagSummary;
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
     * Filter available tags optionally by tag name, project key and limit and offset for paging
     *
     * @http GET /tag
     *
     * @param string $filterString
     * @param string $projectKey
     * @param int $offset
     * @param int $limit
     *
     * @return \Kiniauth\Objects\MetaData\TagSummary[]
     */
    public function filterAvailableTags($filterString = "", $projectKey = null, $offset = 0, $limit = 10) {
        return $this->metaDataService->filterAvailableTags($filterString, $projectKey, $offset, $limit);
    }


    /**
     * Save a tag, optionally for a project
     *
     * @http POST /tag
     *
     * @param TagSummary $tagSummary
     * @param string $projectKey
     */
    public function saveTag($tagSummary, $projectKey = null) {
        $this->metaDataService->saveTag($tagSummary, $projectKey);
    }


    /**
     * Remove a tag, optionally for a project - the key is the payload in this case
     *
     * @http DELETE /tag
     *
     * @param string $key
     * @param string $projectKey
     *
     * @throws \Kinikit\Persistence\ORM\Exception\ObjectNotFoundException
     */
    public function removeTag($key, $projectKey = null) {
        $this->metaDataService->removeTag($key, $projectKey = null);
    }

}