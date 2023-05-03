<?php

namespace Studocu\AssertsSchemaForLaravel\Exceptions;

use Exception;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidQueryArgs;

class OpenAPIRequestMismatchException extends Exception
{
    public static function fromInvalidQueryArgs(InvalidQueryArgs $exception): self
    {
        return new self($exception->getMessage());
    }
}
