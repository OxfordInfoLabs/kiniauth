<?php


namespace Kiniauth\Test\Services\Security;


use Kiniauth\Objects\Communication\Email\UserTemplatedEmail;
use Kiniauth\Objects\Security\UserSession;
use Kiniauth\Objects\Security\UserSessionProfile;
use Kiniauth\Services\Communication\Email\EmailService;
use Kiniauth\Services\Security\UserSessionService;
use Kiniauth\Test\TestBase;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\MVC\Request\Headers;
use Kinikit\MVC\Request\Request;
use Kinikit\MVC\Session\Session;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;

include_once __DIR__ . "/../../autoloader.php";

class UserSessionServiceTest extends TestBase {


    /**
     * @var UserSessionService
     */
    private $userSessionService;


    /**
     * @var MockObject
     */
    private $session;

    /**
     * @var MockObject
     */
    private $emailService;


    public function setUp(): void {

        /**
         * @var MockObjectProvider $mockObjectProvider
         */
        $mockObjectProvider = Container::instance()->get(MockObjectProvider::class);

        $this->session = $mockObjectProvider->getMockInstance(Session::class);
        $this->emailService = $mockObjectProvider->getMockInstance(EmailService::class);

        $this->userSessionService = new UserSessionService($this->session, $this->emailService);


        AuthenticationHelper::login("admin@kinicart.com", "password");

        $databaseConnection = Container::instance()->get(DatabaseConnection::class);
        $databaseConnection->execute("DELETE FROM ka_user_session");
        $databaseConnection->execute("DELETE FROM ka_user_session_profile");


    }


    public function testCanRecordNewAuthenticatedSessionForUser() {


        $_SERVER["REMOTE_ADDR"] = "1.1.1.1";
        $_SERVER['HTTP_USER_AGENT'] = "mytestagent/1.1";
        $request = new Request(new Headers());

        $this->session->returnValue("getId", "1234567");

        // Register a new authenticated session
        $this->userSessionService->registerNewAuthenticatedSession(2, $request);

        $sessions = UserSession::filter("ORDER BY created_date_time DESC");
        $this->assertTrue(sizeof($sessions) > 0);

        /**
         * @var UserSession $lastSession
         */
        $lastSession = $sessions[0];
        $this->assertEquals(2, $lastSession->getUserId());
        $this->assertEquals("1234567", $lastSession->getSessionId());
        $this->assertNotNull($lastSession->getCreatedDateTime());
        $this->assertEquals(new UserSessionProfile("1.1.1.1", "mytestagent/1.1", 2), $lastSession->getProfile());

    }


    public function testIfMoreThanOneIPAddressPassedOnlyFirstIsStoredForReference() {

        $_SERVER["REMOTE_ADDR"] = "7.7.7.7, 1.2.3.4, 5.4.3.2";
        $_SERVER['HTTP_USER_AGENT'] = "mytestagent/1.1";
        $request = new Request(new Headers());

        $this->session->returnValue("getId", "1234567");

        // Register a new authenticated session
        $this->userSessionService->registerNewAuthenticatedSession(2, $request);

        $sessions = UserSession::filter("ORDER BY created_date_time DESC");
        $this->assertTrue(sizeof($sessions) > 0);

        /**
         * @var UserSession $lastSession
         */
        $lastSession = $sessions[0];
        $this->assertEquals(2, $lastSession->getUserId());
        $this->assertEquals("1234567", $lastSession->getSessionId());
        $this->assertNotNull($lastSession->getCreatedDateTime());
        $this->assertEquals(new UserSessionProfile("7.7.7.7", "mytestagent/1.1", 2), $lastSession->getProfile());


    }


    public function testIfNewProfileCreatedForUserEmailIsSentProvidedNotFirstTimeEntry() {


        $_SERVER["REMOTE_ADDR"] = "1.1.1.1";
        $_SERVER['HTTP_USER_AGENT'] = "mytestagent/1.1";
        $request = new Request(new Headers());

        $this->session->returnValue("getId", "1234567");

        // Register a new authenticated session
        $this->userSessionService->registerNewAuthenticatedSession(3, $request);

        $sessions = UserSession::filter("WHERE userId = 3 ORDER BY created_date_time DESC");
        $this->assertEquals(1, sizeof($sessions));

        /**
         * @var UserSession $lastSession
         */
        $lastSession = $sessions[0];
        $this->assertEquals(3, $lastSession->getUserId());
        $this->assertFalse($this->emailService->methodWasCalled("send"));

        // Change remote address
        $_SERVER["REMOTE_ADDR"] = "2.2.2.2";
        $_SERVER['HTTP_USER_AGENT'] = "mytestagent/1.1";
        $request = new Request(new Headers());

        $this->session->returnValue("getId", "2345678");
        $this->userSessionService->registerNewAuthenticatedSession(3, $request);

        $sessions = UserSession::filter("WHERE userId = 3 ORDER BY created_date_time DESC");
        $this->assertEquals(2, sizeof($sessions));


        $expectedEmail = new UserTemplatedEmail(3, "security/new-device", [
            "ipAddress" => "2.2.2.2",
            "userAgent" => "mytestagent/1.1"]);

        $this->assertTrue($this->emailService->methodWasCalled("send", [
            $expectedEmail,
            null,
            3
        ]));


        // Change user agent
        $_SERVER["REMOTE_ADDR"] = "2.2.2.2";
        $_SERVER['HTTP_USER_AGENT'] = "brandnewagent/1.1";
        $request = new Request(new Headers());

        $this->session->returnValue("getId", "3456789");

        $this->emailService->resetMethodCallHistory("send");

        $this->userSessionService->registerNewAuthenticatedSession(3, $request);

        $sessions = UserSession::filter("WHERE userId = 3 ORDER BY created_date_time DESC");
        $this->assertEquals(3, sizeof($sessions));


        $expectedEmail = new UserTemplatedEmail(3, "security/new-device", [
            "ipAddress" => "2.2.2.2",
            "userAgent" => "brandnewagent/1.1"]);

        $this->assertTrue($this->emailService->methodWasCalled("send", [
            $expectedEmail,
            null,
            3
        ]));


        // Keep the same and check no email was sent
        $this->session->returnValue("getId", "4567890");

        $this->emailService->resetMethodCallHistory("send");

        $this->userSessionService->registerNewAuthenticatedSession(3, $request);

        $sessions = UserSession::filter("WHERE userId = 3 ORDER BY created_date_time DESC");
        $this->assertEquals(4, sizeof($sessions));

        $this->assertFalse($this->emailService->methodWasCalled("send"));


    }


    public function testListAuthenticatedSessionsReturnsActiveAuthenticatedSessionsAndCleansUpExpiredSessions() {

        // Create 5 sessions
        (new UserSession(4, "ABCDEFG", new UserSessionProfile("1.1.1.1", "html/1.1", 4)))->save();
        (new UserSession(4, "BCDEFGH", new UserSessionProfile("1.1.1.1", "html/1.1", 4)))->save();
        (new UserSession(4, "CDEFGHI", new UserSessionProfile("1.1.1.1", "html/1.1", 4)))->save();
        (new UserSession(4, "DEFGHIJ", new UserSessionProfile("1.1.1.1", "html/1.1", 4)))->save();
        (new UserSession(4, "EFGHIJK", new UserSessionProfile("1.1.1.1", "html/1.1", 4)))->save();

        $this->session->returnValue("isActive", true, ["ABCDEFG"]);
        $this->session->returnValue("isActive", false, ["BCDEFGH"]);
        $this->session->returnValue("isActive", true, ["CDEFGHI"]);
        $this->session->returnValue("isActive", true, ["DEFGHIJ"]);
        $this->session->returnValue("isActive", false, ["EFGHIJK"]);


        $authenticatedSessions = $this->userSessionService->listAuthenticatedSessions(4);
        $this->assertEquals(3, sizeof($authenticatedSessions));
        $this->assertTrue($authenticatedSessions[0] instanceof UserSession);
        $this->assertTrue($authenticatedSessions[1] instanceof UserSession);
        $this->assertTrue($authenticatedSessions[2] instanceof UserSession);

        $sessionIds = ObjectArrayUtils::getMemberValueArrayForObjects("sessionId", $authenticatedSessions);
        $this->assertTrue(in_array("ABCDEFG", $sessionIds));
        $this->assertTrue(in_array("CDEFGHI", $sessionIds));
        $this->assertTrue(in_array("DEFGHIJ", $sessionIds));

        // Check that now there are only 3 left in the database.
        $this->assertEquals(3, sizeof(UserSession::filter("WHERE userId = 4")));

    }


    public function testCanTerminateAuthenticatedSession() {

        (new UserSession(4, "ABCDEFG", new UserSessionProfile("1.1.1.1", "html/1.1", 4)))->save();

        $this->userSessionService->terminateAuthenticatedSession(4, "ABCDEFG");

        // Check that now there are no sessions left in database
        $this->assertEquals(0, sizeof(UserSession::filter("WHERE userId = 4")));


        // Check session was destroyed
        $this->assertTrue($this->session->methodWasCalled("destroy", ["ABCDEFG"]));
    }


}
