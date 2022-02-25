<?php


namespace Kiniauth\Services\MetaData;


use Kiniauth\Objects\MetaData\Category;
use Kiniauth\Objects\MetaData\CategorySummary;
use Kiniauth\Objects\MetaData\ObjectCategory;
use Kiniauth\Objects\MetaData\ObjectTag;
use Kiniauth\Objects\MetaData\Tag;
use Kiniauth\Objects\MetaData\TagSummary;
use Kiniauth\Services\MetaData\MetaDataService;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\AccessDeniedException;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;

include_once __DIR__ . "/../../autoloader.php";

class MetaDataServiceTest extends TestBase {

    /**
     * @var MetaDataService
     */
    private $service;

    public function setUp(): void {
        $this->service = Container::instance()->get(MetaDataService::class);
    }

    public function testCanFilterAllGlobalTagsWhereNoAccountOrProjectSupplied() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Get global tags
        $globalTags = $this->service->filterAvailableTags("", null, 0, 10, null);

        $this->assertEquals([
            new TagSummary("Global", "A truly global tag available to whole system", "global")
        ], $globalTags);


    }

    public function testFilterTagsLimitsToAccountWhenAccountSupplied() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Get global tags
        $globalTags = $this->service->filterAvailableTags("", null, 0, 10, 1);

        $this->assertEquals([
            new TagSummary("Account1", "An account wide tag available to account 1", "account1")
        ], $globalTags);




    }

    public function testCanFilterAllTagsIncludingProjectOnesWhenProjectNumberSupplied() {

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        // Get global tags
        $tags = $this->service->filterAvailableTags("", "soapSuds", 0, 10, 2);

        $this->assertEquals([
            new TagSummary("Account2", "An account wide tag available to account 2", "account2"),
            new TagSummary("Project", "A project level tag available to just one project", "project")],
            $tags);


        // Check one with a title filter applied
        $tags = $this->service->filterAvailableTags("t", "soapSuds", 0, 10, 2);

        $this->assertEquals([
            new TagSummary("Account2", "An account wide tag available to account 2", "account2"),
            new TagSummary("Project", "A project level tag available to just one project", "project")],
            $tags);

        // Limit
        $tags = $this->service->filterAvailableTags("", "soapSuds", 0, 1, 2);


        $this->assertEquals([
            new TagSummary("Account2", "An account wide tag available to account 2", "account2")],
            $tags);

        // Offset
        $tags = $this->service->filterAvailableTags("", "soapSuds", 1, 10, 2);

        $this->assertEquals([
            new TagSummary("Project", "A project level tag available to just one project", "project")],
            $tags);


    }


    public function testCanCreateTopLevelTagsIfSuperUser() {

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        try {
            $tagSummary = new TagSummary("Peanut Butter", "Peanut Butter Tag");
            $this->service->saveTag($tagSummary, null, null);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            $this->assertTrue(true);
        }

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $tagSummary = new TagSummary("Peanut Butter", "Peanut Butter Tag");
        $key = $this->service->saveTag($tagSummary);
        $this->assertEquals("peanutButter", $key);

        // Get global tags
        $globalTags = $this->service->filterAvailableTags("", null, 0, 10, null);

        $this->assertEquals([
            new TagSummary("Global", "A truly global tag available to whole system", "global"),
            new TagSummary("Peanut Butter", "Peanut Butter Tag", "peanutButter")
        ], $globalTags);


        // Check duplicate one
        $tagSummary = new TagSummary("Peanut Butter", "Peanut Butter Tag");
        $key = $this->service->saveTag($tagSummary);
        $this->assertEquals("peanutButter2", $key);
    }


    public function testCanCreateTagsAtAccountLevel() {
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $tagSummary = new TagSummary("Account Tag", "Account Tag");
        $key = $this->service->saveTag($tagSummary, null, 1);
        $this->assertEquals("accountTag", $key);

        $accountTags = $this->service->filterAvailableTags("", null, 0, 10, 1);
        $this->assertEquals(new TagSummary("Account Tag", "Account Tag", "accountTag"), $accountTags[0]);

        // Check duplicate one
        $tagSummary = new TagSummary("Account Tag", "Account Tag");
        $key = $this->service->saveTag($tagSummary, null, 1);
        $this->assertEquals("accountTag2", $key);

    }

    public function testCanCreateTagsAtProjectLevel() {
        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $tagSummary = new TagSummary("Project Tag", "Project Tag");
        $key = $this->service->saveTag($tagSummary, "wiperBlades", 2);
        $this->assertEquals("projectTag", $key);

        $projectTags = $this->service->filterAvailableTags("", "wiperBlades", 0, 10, 2);
        $this->assertEquals(new TagSummary("Project Tag", "Project Tag", "projectTag"), array_pop($projectTags));


        // Check duplicate one
        $tagSummary = new TagSummary("Project Tag", "Project Tag");
        $key = $this->service->saveTag($tagSummary, "wiperBlades", 2);
        $this->assertEquals("projectTag2", $key);

        // Check different project same tag name
        $tagSummary = new TagSummary("Project Tag", "Project Tag");
        $key = $this->service->saveTag($tagSummary, "pressureWashing", 2);
        $this->assertEquals("projectTag", $key);
    }


    public function testCanRemoveTagsAtSpecifiedScope() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $tagSummary = new TagSummary("Top Level");
        $this->service->saveTag($tagSummary);

        $tagSummary = new TagSummary("Shared Account");
        $this->service->saveTag($tagSummary, null, 1);

        $tagSummary = new TagSummary("Shared Account");
        $this->service->saveTag($tagSummary, null, 2);

        $tagSummary = new TagSummary("Shared Project");
        $this->service->saveTag($tagSummary, "wiperBlades", 2);

        $tagSummary = new TagSummary("Shared Project");
        $this->service->saveTag($tagSummary, "pressureWashing", 2);

        // Check removal of top level tag
        $this->assertEquals(1, sizeof(Tag::filter("WHERE key = 'topLevel'")));
        $this->service->removeTag("topLevel");
        $this->assertEquals(0, sizeof(Tag::filter("WHERE key = 'topLevel'")));

        // Check removal of account level tag
        $this->assertEquals(2, sizeof(Tag::filter("WHERE key = 'sharedAccount'")));
        $this->service->removeTag("sharedAccount", null, 1);
        $this->assertEquals(1, sizeof(Tag::filter("WHERE key = 'sharedAccount'")));
        $this->service->removeTag("sharedAccount", null, 2);
        $this->assertEquals(0, sizeof(Tag::filter("WHERE key = 'sharedAccount'")));

        // Check removal of project level tag
        $this->assertEquals(2, sizeof(Tag::filter("WHERE key = 'sharedProject'")));
        $this->service->removeTag("sharedProject", "wiperBlades", 2);
        $this->assertEquals(1, sizeof(Tag::filter("WHERE key = 'sharedProject'")));
        $this->service->removeTag("sharedProject", "pressureWashing", 2);
        $this->assertEquals(0, sizeof(Tag::filter("WHERE key = 'sharedProject'")));

    }


    public function testCanGetObjectTagsFromSummariesForContext() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $tagSummary = new TagSummary("Top Level");
        $this->service->saveTag($tagSummary);

        $tagSummary = new TagSummary("Shared Account");
        $this->service->saveTag($tagSummary, null, 1);

        $tagSummary = new TagSummary("Shared Account");
        $this->service->saveTag($tagSummary, null, 2);

        $tagSummary = new TagSummary("Shared Project");
        $this->service->saveTag($tagSummary, "wiperBlades", 2);

        $tagSummary = new TagSummary("Shared Project");
        $this->service->saveTag($tagSummary, "pressureWashing", 2);


        $fullTags = $this->service->getObjectTagsFromSummaries([
            new TagSummary("topLevel", "", "topLevel"),
            new TagSummary("sharedAccount", "", "sharedAccount"),
            new TagSummary("sharedProject", "", "sharedProject")
        ], 2, "wiperBlades");

        $this->assertEquals(2, sizeof($fullTags));
        $this->assertInstanceOf(ObjectTag::class, $fullTags[0]);
        $this->assertEquals("sharedAccount", $fullTags[0]->getTag()->getKey());
        $this->assertEquals(2, $fullTags[0]->getTag()->getAccountId());
        $this->assertInstanceOf(ObjectTag::class, $fullTags[1]);
        $this->assertEquals("sharedProject", $fullTags[1]->getTag()->getKey());
        $this->assertEquals(2, $fullTags[1]->getTag()->getAccountId());
        $this->assertEquals("wiperBlades", $fullTags[1]->getTag()->getProjectKey());


        $fullTags = $this->service->getObjectTagsFromSummaries([
            new TagSummary("topLevel", "", "topLevel"),
            new TagSummary("sharedAccount", "", "sharedAccount"),
            new TagSummary("sharedProject", "", "sharedProject")
        ], 2, "pressureWashing");

        $this->assertEquals(2, sizeof($fullTags));
        $this->assertInstanceOf(ObjectTag::class, $fullTags[0]);
        $this->assertEquals("sharedAccount", $fullTags[0]->getTag()->getKey());
        $this->assertEquals(2, $fullTags[0]->getTag()->getAccountId());
        $this->assertInstanceOf(ObjectTag::class, $fullTags[1]);
        $this->assertEquals("sharedProject", $fullTags[1]->getTag()->getKey());
        $this->assertEquals(2, $fullTags[1]->getTag()->getAccountId());
        $this->assertEquals("pressureWashing", $fullTags[1]->getTag()->getProjectKey());

    }


    public function testCanFilterAllGlobalCategoriesWhereNoAccountOrProjectSupplied() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Get global tags
        $globalCategories = $this->service->filterAvailableCategories("", null, 0, 10, null);

        $this->assertEquals([
            new CategorySummary("Global", "A truly global category available to whole system", "global")
        ], $globalCategories);


    }

    public function testCanGetFilterCategoriesWhenAccountSupplied() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Get global tags
        $accountCategories = $this->service->filterAvailableCategories("", null, 0, 10, 1);

        $this->assertEquals([
            new CategorySummary("Account1", "An account wide category available to account 1", "account1"),

        ], $accountCategories);

        // Check one with a title filter applied
        // Get global tags
        $accountCategories = $this->service->filterAvailableCategories("lob", null, 0, 10, 1);

        $this->assertEquals([

        ], $accountCategories);


    }

    public function testCanFilterAllCategoriesIncludingProjectOnesWhenProjectNumberSupplied() {

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        // Get global tags
        $categories = $this->service->filterAvailableCategories("", "soapSuds", 0, 10, 2);

        $this->assertEquals([
            new CategorySummary("Account2", "An account wide category available to account 2", "account2"),
             new CategorySummary("Project", "A project level category available to just one project", "project")],
            $categories);


        // Check one with a title filter applied
        $categories = $this->service->filterAvailableCategories("t", "soapSuds", 0, 10, 2);

        $this->assertEquals([
            new CategorySummary("Account2", "An account wide category available to account 2", "account2"),
            new CategorySummary("Project", "A project level category available to just one project", "project")],
            $categories);

        // Limit
        $categories = $this->service->filterAvailableCategories("", "soapSuds", 0, 1, 2);

        $this->assertEquals([
            new CategorySummary("Account2", "An account wide category available to account 2", "account2")],
             $categories);

        // Offset
        $categories = $this->service->filterAvailableCategories("", "soapSuds", 1, 10, 2);

        $this->assertEquals([
             new CategorySummary("Project", "A project level category available to just one project", "project")],
            $categories);


    }


    public function testCanCreateTopLevelCategoriesIfSuperUser() {

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        try {
            $categorySummary = new CategorySummary("Peanut Butter", "Peanut Butter Tag");
            $this->service->saveCategory($categorySummary, null, null);
            $this->fail("Should have thrown here");
        } catch (AccessDeniedException $e) {
            $this->assertTrue(true);
        }

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $categorySummary = new CategorySummary("Peanut Butter", "Peanut Butter Tag");
        $key = $this->service->saveCategory($categorySummary);
        $this->assertEquals("peanutButter", $key);

        // Get global categorys
        $globalTags = $this->service->filterAvailableCategories("", null, 0, 10, null);

        $this->assertEquals([
            new CategorySummary("Global", "A truly global category available to whole system", "global"),
            new CategorySummary("Peanut Butter", "Peanut Butter Tag", "peanutButter")
        ], $globalTags);


        // Check duplicate one
        $categorySummary = new CategorySummary("Peanut Butter", "Peanut Butter Tag");
        $key = $this->service->saveCategory($categorySummary);
        $this->assertEquals("peanutButter2", $key);
    }


    public function testCanCreateCategoriesAtAccountLevel() {
        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        $categorySummary = new CategorySummary("Account Category", "Account Category");
        $key = $this->service->saveCategory($categorySummary, null, 1);
        $this->assertEquals("accountCategory", $key);

        $accountCategorys = $this->service->filterAvailableCategories("", null, 0, 10, 1);
        $this->assertEquals(new CategorySummary("Account Category", "Account Category", "accountCategory"), $accountCategorys[0]);

        // Check duplicate one
        $categorySummary = new CategorySummary("Account Category", "Account Category");
        $key = $this->service->saveCategory($categorySummary, null, 1);
        $this->assertEquals("accountCategory2", $key);

    }

    public function testCanCreateCategoriesAtProjectLevel() {
        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        $categorySummary = new CategorySummary("Project Category", "Project Category");
        $key = $this->service->saveCategory($categorySummary, "wiperBlades", 2);
        $this->assertEquals("projectCategory", $key);

        $projectCategorys = $this->service->filterAvailableCategories("", "wiperBlades", 0, 10, 2);
        $this->assertEquals(new CategorySummary("Project Category", "Project Category", "projectCategory"), array_pop($projectCategorys));


        // Check duplicate one
        $categorySummary = new CategorySummary("Project Category", "Project Category");
        $key = $this->service->saveCategory($categorySummary, "wiperBlades", 2);
        $this->assertEquals("projectCategory2", $key);

        // Check different project same category name
        $categorySummary = new CategorySummary("Project Category", "Project Category");
        $key = $this->service->saveCategory($categorySummary, "pressureWashing", 2);
        $this->assertEquals("projectCategory", $key);
    }


    public function testCanRemoveCategoriesAtSpecifiedScope() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $categorySummary = new CategorySummary("Top Level");
        $this->service->saveCategory($categorySummary);

        $categorySummary = new CategorySummary("Shared Account");
        $this->service->saveCategory($categorySummary, null, 1);

        $categorySummary = new CategorySummary("Shared Account");
        $this->service->saveCategory($categorySummary, null, 2);

        $categorySummary = new CategorySummary("Shared Project");
        $this->service->saveCategory($categorySummary, "wiperBlades", 2);

        $categorySummary = new CategorySummary("Shared Project");
        $this->service->saveCategory($categorySummary, "pressureWashing", 2);

        // Check removal of top level category
        $this->assertEquals(1, sizeof(Category::filter("WHERE key = 'topLevel'")));
        $this->service->removeCategory("topLevel");
        $this->assertEquals(0, sizeof(Category::filter("WHERE key = 'topLevel'")));

        // Check removal of account level category
        $this->assertEquals(2, sizeof(Category::filter("WHERE key = 'sharedAccount'")));
        $this->service->removeCategory("sharedAccount", null, 1);
        $this->assertEquals(1, sizeof(Category::filter("WHERE key = 'sharedAccount'")));
        $this->service->removeCategory("sharedAccount", null, 2);
        $this->assertEquals(0, sizeof(Category::filter("WHERE key = 'sharedAccount'")));

        // Check removal of project level category
        $this->assertEquals(2, sizeof(Category::filter("WHERE key = 'sharedProject'")));
        $this->service->removeCategory("sharedProject", "wiperBlades", 2);
        $this->assertEquals(1, sizeof(Category::filter("WHERE key = 'sharedProject'")));
        $this->service->removeCategory("sharedProject", "pressureWashing", 2);
        $this->assertEquals(0, sizeof(Category::filter("WHERE key = 'sharedProject'")));

    }


    public function testCanGetObjectCategoriesFromSummariesForContext() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $categorySummary = new CategorySummary("Top Level");
        $this->service->saveCategory($categorySummary);

        $categorySummary = new CategorySummary("Shared Account", "Belongs to account 1");
        $this->service->saveCategory($categorySummary, null, 1);

        $categorySummary = new CategorySummary("Shared Account", "Belongs to account 2");
        $this->service->saveCategory($categorySummary, null, 2);

        $categorySummary = new CategorySummary("Shared Project", "Belongs to wiperBlades");
        $this->service->saveCategory($categorySummary, "wiperBlades", 2);

        $categorySummary = new CategorySummary("Shared Project", "Belongs to pressureWashing");
        $this->service->saveCategory($categorySummary, "pressureWashing", 2);


        $fullCategories = $this->service->getObjectCategoriesFromSummaries([
            new CategorySummary("topLevel", "", "topLevel"),
            new CategorySummary("sharedAccount", "", "sharedAccount"),
            new CategorySummary("sharedProject", "", "sharedProject")
        ], 2, "wiperBlades");

        $this->assertEquals(2, sizeof($fullCategories));
         $this->assertInstanceOf(ObjectCategory::class, $fullCategories[0]);
        $this->assertEquals("sharedAccount", $fullCategories[0]->getCategory()->getKey());
        $this->assertEquals(2, $fullCategories[0]->getCategory()->getAccountId());
        $this->assertInstanceOf(ObjectCategory::class, $fullCategories[1]);
        $this->assertEquals("sharedProject", $fullCategories[1]->getCategory()->getKey());
        $this->assertEquals(2, $fullCategories[1]->getCategory()->getAccountId());
        $this->assertEquals("wiperBlades", $fullCategories[1]->getCategory()->getProjectKey());


        $fullCategories = $this->service->getObjectCategoriesFromSummaries([
            new CategorySummary("topLevel", "", "topLevel"),
            new CategorySummary("sharedAccount", "", "sharedAccount"),
            new CategorySummary("sharedProject", "", "sharedProject")
        ], 2, "pressureWashing");

        $this->assertEquals(2, sizeof($fullCategories));
        $this->assertInstanceOf(ObjectCategory::class, $fullCategories[0]);
        $this->assertEquals("sharedAccount", $fullCategories[0]->getCategory()->getKey());
        $this->assertEquals(2, $fullCategories[0]->getCategory()->getAccountId());
        $this->assertInstanceOf(ObjectCategory::class, $fullCategories[1]);
        $this->assertEquals("sharedProject", $fullCategories[1]->getCategory()->getKey());
        $this->assertEquals(2, $fullCategories[1]->getCategory()->getAccountId());
        $this->assertEquals("pressureWashing", $fullCategories[1]->getCategory()->getProjectKey());

    }

    public function testCanGetMultipleCategoriesByKeyForAccountAndProject() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $this->assertEquals([
            new CategorySummary("Shared Account", "Belongs to account 1", "sharedAccount")
        ], $this->service->getMultipleCategoriesByKey([
            "topLevel",
            "sharedAccount"
        ], null, 1));


        $this->assertEquals([
            new CategorySummary("Shared Account", "Belongs to account 2", "sharedAccount")
        ], $this->service->getMultipleCategoriesByKey([
            "topLevel",
            "sharedAccount"
        ], null, 2));



        $this->assertEquals([
            new CategorySummary("Shared Project", "Belongs to wiperBlades", "sharedProject"),
        ], $this->service->getMultipleCategoriesByKey([
            "sharedProject",
        ], "wiperBlades", 2));

    }


}