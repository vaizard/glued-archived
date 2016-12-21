<?php

// this class is intended to simplify usage and extensibility of
// Respect\Validation with custom rules

namespace Glued\Classes\Validation;

use Respect\Validation\Validator as Respect;
use Respect\Validation\Exceptions\NestedValidationException;

class Validator
{
    protected $errors;


    // validates an array, throws exception on failure
    public function validate($request, array $rules)
    {
        // for each array member we assert the parameter (name uppercase
        // first letter). if something fails the validation, an exception
        // is thrown.
        foreach ($rules as $field => $rule) {
            try {
                $rule->setName(ucfirst($field))->assert($request->getParam($field));
            } catch (NestedValidationException $e) {
                $this->errors[$field] = $e->getMessages();
            }
        }

        // DEBUG
        // var_dump($this->errors); die();

        // we pass the error messages via the user's session
        $_SESSION['validationerrors'] = $this->errors;
        return $this;
    }


    // returns true|false if validation failed|passed
    public function failed()
    {
        return !empty($this->errors);
    }

}
