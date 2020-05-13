<?php

namespace Kiniauth\Test\Services\Workflow\Validation;

use Kiniauth\Services\Workflow\Validation\PasswordFieldValidator;
use Kiniauth\Test\TestBase;

class PasswordFieldValidatorTest extends TestBase {

    /**
     * @var PasswordFieldValidator
     */
    private $validator;

    /**
     * Set up
     */
    public function setUp(): void {
        $this->validator = new PasswordFieldValidator("password");
    }

    public function testPasswordMustBeAtLeast8Chars() {

        $this->assertFalse($this->validate("Aa1"));
        $this->assertFalse($this->validate("Aa1a"));
        $this->assertFalse($this->validate("Aa1ab"));
        $this->assertFalse($this->validate("Aa1abc"));
        $this->assertFalse($this->validate("Aa1abcd"));
        $this->assertTrue($this->validate("Aa1abcde"));
        $this->assertTrue($this->validate("Aa1abcdef"));

    }

    public function testPasswordMustContainOneCapitalLetterOneLowerCaseLetterAndOneNumber() {
        $this->assertFalse($this->validate("abcdefgh"));
        $this->assertFalse($this->validate("Abcdefgh"));
        $this->assertTrue($this->validate("Abcdefg3"));
        $this->assertTrue($this->validate("abcdeF4g"));
    }


    private function validate($value) {
        $params = [];
        return $this->validator->validateObjectFieldValue($value, null, null, $params);
    }

}
