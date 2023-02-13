<?php


namespace Kiniauth\Test\Objects\Security;

use Kiniauth\Exception\Security\PasswordUsedBeforeException;
use Kiniauth\Objects\Security\User;
use Kiniauth\Test\Services\Security\AuthenticationHelper;
use Kiniauth\Test\TestBase;

include_once "autoloader.php";

class UserTest extends TestBase {


    /**
     * @nontravis
     */
    public function testCannotUpdatePasswordWithPreviouslyUsedHash() {

        AuthenticationHelper::login("admin@kinicart.com", "password");

        $user = new User("hello@myworld.com", hash("sha512", "newpassword1"), "Zebra World");
        $user->save();

        // Update to new password
        $user->setHashedPassword(hash("sha512", "newpassword2"));
        $user->save();

        try {
            $user->setHashedPassword(hash("sha512", "newpassword1"));
            $user->save();
            $this->fail("Should have thrown here");
        } catch (PasswordUsedBeforeException $e) {
            $this->assertTrue(true);
        }


    }

}