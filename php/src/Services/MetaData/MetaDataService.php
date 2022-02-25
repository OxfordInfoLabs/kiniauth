<?php


namespace Kiniauth\Services\MetaData;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Objects\MetaData\Category;
use Kiniauth\Objects\MetaData\CategorySummary;
use Kiniauth\Objects\MetaData\ObjectCategory;
use Kiniauth\Objects\MetaData\ObjectTag;
use Kiniauth\Objects\MetaData\Tag;
use Kiniauth\Objects\MetaData\TagSummary;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use PHPMailer\PHPMailer\Exception;

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
        $tag = new Tag($tagSummary, $accountId, $projectKey ? $projectKey : null);
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
            $clauses[] = "(accountId = ?)";
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


    /**
     * Filter available tags - optionally scoped to account and project.
     *
     * @param string $filterString
     * @param string $projectKey
     * @param int $offset
     * @param int $limit
     * @param integer $accountId
     * @return CategorySummary[]
     */
    public function filterAvailableCategories($filterString = "", $projectKey = null, $offset = 0, $limit = 10, $accountId = Account::LOGGED_IN_ACCOUNT) {
        return array_map(function ($category) {
            return new CategorySummary($category->getCategory(), $category->getDescription(), $category->getKey());
        }, $this->filterRawAvailableCategories($filterString, $projectKey, $accountId, $offset, $limit));
    }


    /**
     * Get multiple categories by key
     *
     * @param string[] $keys
     * @param string $projectKey
     * @param string $accountId
     *
     * @return CategorySummary[]
     */
    public function getMultipleCategoriesByKey($keys, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        if (sizeof($keys) == 0)
            return [];

        $clauses = ["key IN (?" . str_repeat(",?", sizeof($keys) - 1) . ")"];
        $params = $keys;

        if ($accountId) {
            $clauses[] = "(accountId = ?)";
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

        $matches = Category::filter("WHERE " . join(" AND ", $clauses) . " ORDER BY category", $params);
        return array_map(function ($category) {
            return new CategorySummary($category->getCategory(), $category->getDescription(), $category->getKey());
        }, $matches);


    }


    /**
     * Get an array of object tags from a summary array for the context supplied
     * by accountId and projectKey - ready for attachment to child objects
     *
     * @param CategorySummary[] $categorySummaries
     * @param string $accountId
     * @param string $projectKey
     *
     * @return ObjectCategory[]
     */
    public function getObjectCategoriesFromSummaries($categorySummaries, $accountId = Account::LOGGED_IN_ACCOUNT, $projectKey = null) {

        // Get the available tags
        $availableCategories = ObjectArrayUtils::indexArrayOfObjectsByMember("key", $this->filterRawAvailableCategories("", $projectKey, $accountId, 0, PHP_INT_MAX));

        $matches = [];
        foreach ($categorySummaries as $summary) {
            if (isset($availableCategories[$summary->getKey()])) {
                $matches[] = new ObjectCategory($availableCategories[$summary->getKey()]);
            }
        }

        return $matches;

    }


    /**
     * Save a tag and return the tag key
     *
     * @param $categorySummary
     * @param string $projectKey
     * @param string $accountId
     *
     * @return string
     */
    public function saveCategory($categorySummary, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

        // Create new tag
        $category = new Category($categorySummary, $accountId, $projectKey ? $projectKey : null);
        $category->save();
        return $category->getKey();

    }


    /**
     * Remove a category at the specified scope
     *
     * @param $key
     * @param null $projectKey
     * @param string $accountId
     */
    public function removeCategory($key, $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT) {

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

        $tags = Category::filter("WHERE $clause", $params);
        if (sizeof($tags) > 0) {
            $tags[0]->remove();
        } else {
            throw new ObjectNotFoundException(Category::class, [$accountId, $projectKey, $key]);
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
    private function filterRawAvailableCategories($filterString = "", $projectKey = null, $accountId = Account::LOGGED_IN_ACCOUNT, $offset = 0, $limit = 10) {

        $clauses = [];
        $params = [];

        if ($filterString) {
            $clauses[] = "category LIKE ?";
            $params[] = "%$filterString%";
        }

        if ($accountId) {
            $clauses[] = "(accountId = ?)";
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
        $results = Category::filter("WHERE " . join(" AND ", $clauses) . " ORDER BY category", $params);
        return array_slice($results, $offset, $limit);

    }


}