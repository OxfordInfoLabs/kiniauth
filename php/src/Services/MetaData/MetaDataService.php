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
     * Get available tags - optionally scoped to account and project
     *
     * @param integer $accountId
     * @param string $projectKey
     * @return TagSummary[]
     */
    public function getAvailableTags($accountId = Account::LOGGED_IN_ACCOUNT, $projectKey = null) {
        return array_map(function ($tag) {
            return new TagSummary($tag->getTag(), $tag->getDescription(), $tag->getKey());
        }, $this->getRawAvailableTags($accountId, $projectKey));
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
        $availableTags = ObjectArrayUtils::indexArrayOfObjectsByMember("key", $this->getRawAvailableTags($accountId, $projectKey));

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
     * @param string $accountId
     * @param string $projectKey
     *
     * @return string
     */
    public function saveTag($tagSummary, $accountId = Account::LOGGED_IN_ACCOUNT, $projectKey = null) {

        // Create new tag
        $tag = new Tag($tagSummary, $accountId, $projectKey);
        $tag->save();
        return $tag->getKey();

    }


    /**
     * Remove a tag at the specified scope
     *
     * @param $key
     * @param string $accountId
     * @param null $projectKey
     */
    public function removeTag($key, $accountId = Account::LOGGED_IN_ACCOUNT, $projectKey = null) {

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
     * @param string $accountId
     * @param null $projectKey
     * @return mixed
     */
    private function getRawAvailableTags($accountId = Account::LOGGED_IN_ACCOUNT, $projectKey = null) {

        $clauses = [];
        $params = [];
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

        return Tag::filter("WHERE " . join(" AND ", $clauses) . " ORDER BY tag", $params);

    }


}