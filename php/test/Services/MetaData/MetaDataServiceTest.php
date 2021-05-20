<?php


namespace Kiniauth\Services\MetaData;


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

    public function testCanGetFilterTagsIncludingGlobalOnesWhenAccountSupplied() {

        AuthenticationHelper::login("sam@samdavisdesign.co.uk", "password");

        // Get global tags
        $globalTags = $this->service->filterAvailableTags("", null, 0, 10, 1);

        $this->assertEquals([
            new TagSummary("Account1", "An account wide tag available to account 1", "account1"),
            new TagSummary("Global", "A truly global tag available to whole system", "global"),

        ], $globalTags);

        // Check one with a title filter applied
        // Get global tags
        $globalTags = $this->service->filterAvailableTags("lob", null, 0, 10, 1);

        $this->assertEquals([
            new TagSummary("Global", "A truly global tag available to whole system", "global"),

        ], $globalTags);


    }

    public function testCanFilterAllTagsIncludingProjectOnesWhenProjectNumberSupplied() {

        AuthenticationHelper::login("simon@peterjonescarwash.com", "password");

        // Get global tags
        $tags = $this->service->filterAvailableTags("", "soapSuds", 0, 10, 2);

        $this->assertEquals([
            new TagSummary("Account2", "An account wide tag available to account 2", "account2"),
            new TagSummary("Global", "A truly global tag available to whole system", "global"),
            new TagSummary("Project", "A project level tag available to just one project", "project")],
            $tags);


        // Check one with a title filter applied
        $tags = $this->service->filterAvailableTags("t", "soapSuds", 0, 10, 2);

        $this->assertEquals([
            new TagSummary("Account2", "An account wide tag available to account 2", "account2"),
            new TagSummary("Project", "A project level tag available to just one project", "project")],
            $tags);

        // Limit
        $tags = $this->service->filterAvailableTags("", "soapSuds", 0, 2, 2);

        $this->assertEquals([
            new TagSummary("Account2", "An account wide tag available to account 2", "account2"),
            new TagSummary("Global", "A truly global tag available to whole system", "global")],
            $tags);

        // Offset
        $tags = $this->service->filterAvailableTags("", "soapSuds", 1, 10, 2);

        $this->assertEquals([
            new TagSummary("Global", "A truly global tag available to whole system", "global"),
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

        $this->assertEquals(3, sizeof($fullTags));
        $this->assertInstanceOf(ObjectTag::class, $fullTags[0]);
        $this->assertEquals("topLevel", $fullTags[0]->getTag()->getKey());
        $this->assertInstanceOf(ObjectTag::class, $fullTags[1]);
        $this->assertEquals("sharedAccount", $fullTags[1]->getTag()->getKey());
        $this->assertEquals(2, $fullTags[1]->getTag()->getAccountId());
        $this->assertInstanceOf(ObjectTag::class, $fullTags[2]);
        $this->assertEquals("sharedProject", $fullTags[2]->getTag()->getKey());
        $this->assertEquals(2, $fullTags[2]->getTag()->getAccountId());
        $this->assertEquals("wiperBlades", $fullTags[2]->getTag()->getProjectKey());


        $fullTags = $this->service->getObjectTagsFromSummaries([
            new TagSummary("topLevel", "", "topLevel"),
            new TagSummary("sharedAccount", "", "sharedAccount"),
            new TagSummary("sharedProject", "", "sharedProject")
        ], 2, "pressureWashing");

        $this->assertEquals(3, sizeof($fullTags));
        $this->assertInstanceOf(ObjectTag::class, $fullTags[0]);
        $this->assertEquals("topLevel", $fullTags[0]->getTag()->getKey());
        $this->assertInstanceOf(ObjectTag::class, $fullTags[1]);
        $this->assertEquals("sharedAccount", $fullTags[1]->getTag()->getKey());
        $this->assertEquals(2, $fullTags[1]->getTag()->getAccountId());
        $this->assertInstanceOf(ObjectTag::class, $fullTags[2]);
        $this->assertEquals("sharedProject", $fullTags[2]->getTag()->getKey());
        $this->assertEquals(2, $fullTags[2]->getTag()->getAccountId());
        $this->assertEquals("pressureWashing", $fullTags[2]->getTag()->getProjectKey());

    }

}