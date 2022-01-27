<?php


namespace Kiniauth\Traits\Controller\Admin;


use Kiniauth\Objects\MetaData\CategorySummary;
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
     * @param int $accountId
     *
     * @return \Kiniauth\Objects\MetaData\TagSummary[]
     */
    public function filterAvailableTags($filterString = "", $projectKey = null, $offset = 0, $limit = 10, $accountId = 0) {
        return $this->metaDataService->filterAvailableTags($filterString, $projectKey, $offset, $limit, $accountId);
    }


    /**
     * Save a tag, optionally for a project
     *
     * @http POST /tag
     *
     * @param TagSummary $tagSummary
     * @param string $projectKey
     * @param int $accountId
     */
    public function saveTag($tagSummary, $projectKey = null, $accountId = 0) {
        $this->metaDataService->saveTag($tagSummary, $projectKey, $accountId);
    }


    /**
     * Remove a tag, optionally for a project - the key is the payload in this case
     *
     * @http DELETE /tag
     *
     * @param string $key
     * @param string $projectKey
     * @param integer $accountId
     *
     * @throws \Kinikit\Persistence\ORM\Exception\ObjectNotFoundException
     */
    public function removeTag($key, $projectKey = null, $accountId = 0) {
        $this->metaDataService->removeTag($key, $projectKey = null, $accountId);
    }


    /**
     * Filter available categories optionally by filter string, project key and limit and offset for paging
     *
     * @http GET /category
     *
     * @param string $filterString
     * @param string $projectKey
     * @param int $offset
     * @param int $limit
     * @param int $accountId
     *
     * @return \Kiniauth\Objects\MetaData\CategorySummary[]
     */
    public function filterAvailableCategories($filterString = "", $projectKey = null, $offset = 0, $limit = 10, $accountId = 0) {
        return $this->metaDataService->filterAvailableCategories($filterString, $projectKey, $offset, $limit, $accountId);
    }


    /**
     * Save a category, optionally for a project
     *
     * @http POST /category
     *
     * @param CategorySummary $categorySummary
     * @param string $projectKey
     * @param int $accountId
     */
    public function saveCategory($categorySummary, $projectKey = null, $accountId = null) {
        $this->metaDataService->saveCategory($categorySummary, $projectKey, $accountId);
    }


    /**
     * Remove a category, optionally for a project - the key is the payload in this case
     *
     * @http DELETE /category
     *
     * @param string $key
     * @param string $projectKey
     * @param integer $accountId
     *
     * @throws \Kinikit\Persistence\ORM\Exception\ObjectNotFoundException
     */
    public function removeCategory($key, $projectKey = null, $accountId = null) {
        $this->metaDataService->removeCategory($key, $projectKey = null, $accountId = null);
    }


}