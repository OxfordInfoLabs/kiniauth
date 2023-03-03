<?php


namespace Kiniauth\Objects\MetaData;


use Kiniauth\Traits\Account\AccountProject;


/**
 * @table ka_tag
 * @generate
 * @interceptor \Kiniauth\Objects\MetaData\TagInterceptor
 */
class Tag extends TagSummary {

    use AccountProject;

    /**
     * @var string
     * @primaryKey
     */
    protected $key;

    /**
     * @var integer
     * @primaryKey
     */
    protected $accountId;


    /**
     * @var string
     * @primaryKey
     */
    protected $projectKey;


    /**
     * TagSummary constructor.
     *
     * @param TagSummary $tagSummary
     * @param string $tag
     * @param string $description
     */
    public function __construct($tagSummary = null, $accountId = null, $projectKey = null) {
        if ($tagSummary instanceof TagSummary)
            parent::__construct($tagSummary->getTag(), $tagSummary->getDescription(), $tagSummary->getKey());
        $this->accountId = $accountId ?? -1;
        $this->projectKey = $projectKey ?? "";
    }

    /**
     * @param string $key
     */
    public function setKey($key) {
        $this->key = $key;
    }


}