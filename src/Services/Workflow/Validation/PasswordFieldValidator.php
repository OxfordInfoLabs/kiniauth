<?php

namespace Kiniauth\Services\Workflow\Validation;


use Kinikit\Core\Validation\FieldValidators\ObjectFieldValidator;
use Kinikit\Core\Validation\FieldValidators\SerialisableObject;


/**
 * Password field validator.
 *
 * Class PasswordFieldValidator
 * @package Kiniauth\Services\Workflow\Validation
 */
class PasswordFieldValidator extends ObjectFieldValidator {


    /**
     * Validate a value
     *
     * @param $value string
     * @param $fieldName
     * @param $targetObject SerialisableObject
     * @param $validatorParams array
     * @param $validatorKey
     * @return mixed
     */
    public function validateObjectFieldValue($value, $fieldName, $targetObject, &$validatorParams, $validatorKey) {

        if (!$value) return true;

        $valid = strlen($value) >= 8;
        $valid = $valid && preg_match("/[A-Z]/", $value);
        $valid = $valid && preg_match("/[a-z]/", $value);
        $valid = $valid && preg_match("/[0-9]/", $value);
        return $valid;
    }
}
