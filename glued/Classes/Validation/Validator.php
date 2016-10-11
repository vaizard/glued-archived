<?php

// we're setting up our own class so that we can use the Respect\Validation
// more easily.

namespace Glued\Classes\Validation;

use Respect\Validation\Validator as Respect;
use Respect\Validation\Exceptions\NestedValidationException;

class Validator
{
    protected $errors;
    public function validate($request, array $rules)
    {
        foreach ($rules as $field => $rule) {
           // for each array member we assert the parameter (name uppercase
           // first letter). if something fails the validation, an exception
           // is thrown.
           try {
             $rule->setName(ucfirst($field))->assert($request->getParam($field));
           } catch (NestedValidationException $e) {
             $this->errors[$field] = $e->getMessages();
           }
        }

        // DEBUG
        // var_dump($this->errors);
        // die();

        // we need to pass the error messages somehow, so we'll user sessions
        $_SESSION['validationerrors'] = $this->errors;
        return $this;
    }

public function failed()
{
    return !empty($this->errors);
}

}