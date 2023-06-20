<?php

namespace Studocu\AssertsSchemaForLaravel\Exceptions;

use Exception;

class OpenAPIComponentNotFoundException extends Exception
{
    public static function fromComponentName(string $componentName): self
    {
        return new self(sprintf('[Component Schema Mismatch] Component [%s] was not found on Schema.', $componentName));
    }
}