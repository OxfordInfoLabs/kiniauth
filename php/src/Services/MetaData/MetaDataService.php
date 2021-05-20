<?php


namespace Kiniauth\Services\MetaData;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\MetaData\ObjectTag;
use Kiniauth\Objects\MetaData\Tag;
use Kiniauth\Objects\MetaData\TagSummary;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

class MetaDataService {


    /**
     * Filter available tags - optionally scoped to account and project.
     *
     * @param string $filterString
     * @param string $projectKey
     * @param int $offset
     * @param int $limit
     * @param integer $accountId
     * @return TagSummary[]
     */
    public function filterAvailableTags($filterString = "", $projectKey = null, $offset = 0, $limit = 10, $accountId = Account::LOGGED_IN_ACCOUNT) {
        return array_map(function ($tag) {
            return new TagSummary($tag->getTag(), $tag->getDescription(), $tag->getKey());
        }, $this->filterRawAvailableTags($filterString, $projectKey, $accountId, $offset, $limit));
    }


    /**
     * Get an array of object tags from a summary array for the context supplied
     * by accountId and projectKey - ready for attachment to child objects
     *
     * @param TagSummary[] $tagSummaries
     * @param string $accountId
     * @param string $projectKey
     *
     * @return ObjectTag[]
     */
    public function getObjectTagsFromSummaries($tagSummaries, $accountId = Account::LOGGED_IN_ACCOUNT, $projectKey = null) {

        // Get the available tags
        $availableTags = ObjectArrayUtils::indexArrayOfObjectsByMember("key", $this->filterRawAvailableTags("", $projectKey, $accountId, 0, PHP_INT_MAX));

        $matches = [];
        foreach ($tagSummaries as $summary) {
            if (isset($availableTags[$summary->getKey()])) {
                $matches[] = new ObjectTag($availableTags[$summary->getKey()]);
            }
        }

        return $matches;

    }


    /**
     * Save a tag and return the tag key
     *
     * @param $tagSummary
     * @param string $projectKey
     * @param string $accountId
     *
     * @return string
     */
    public function saveTag($tagSummary, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // Create new tag
        $tag = new Tag($tagSummary, $accountId, $projectKey);
        $tag->save();
        return $tag->getKey();

    }


    /**
     * Remove a tag at the specified scope
     *
     * @param $key
     * @param null $projectKey
     * @param string $accountId
     */
    public function removeTag($key, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        $clause = "key = ?";
        $params = [$key];

        if ($accountId) {
            $clause .= " AND (account_id IS NULL or account_id = ?)";
            $params[] = $accountId;
        }

        if ($projectKey) {
            $clause .= " AND (project_key IS NULL or project_key = ?)";
            $params[] = $projectKey;
        }

        $tags = Tag::filter("WHERE $clause", $params);
        if (sizeof($tags) > 0) {
            $tags[0]->remove();
        } else {
            throw new ObjectNotFoundException(Tag::class, [$accountId, $projectKey, $key]);
        }


    }


    /**
     * Raw version of get available tags for internal application use
     *
     * @param string $filterString
     * @param null $projectKey
     * @param string $accountId
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    private function filterRawAvailableTags($filterString = "", $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT, $offset = 0, $limit = 10) {

        $clauses = [];
        $params = [];

        if ($filterString) {
            $clauses[] = "tag LIKE ?";
            $params[] = "%$filterString%";
        }

        if ($accountId) {
            $clauses[] = "(accountId = ? OR accountId IS NULL)";
            $params[] = $accountId;
        } else {
            $clauses[] = "(accountId IS NULL)";
        }

        if ($projectKey) {
            $clauses[] = "(projectKey = ? OR projectKey IS NULL)";
            $params[] = $projectKey;
        } else {
            $clauses[] = "(projectKey IS NULL)";
        }

        // Handle the limiting and offsetting in memory for now.
        $results = Tag::filter("WHERE " . join(" AND ", $clauses) . " ORDER BY tag", $params);
        return array_slice($results, $offset, $limit);

    }


}