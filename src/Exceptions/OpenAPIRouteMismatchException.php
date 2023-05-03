<?php

namespace Studocu\AssertsSchemaForLaravel\Exceptions;

use Exception;
use League\OpenAPIValidation\PSR7\Exception\MultipleOperationsMismatchForRequest;
use League\OpenAPIValidation\PSR7\Exception\NoOperation;
use League\OpenAPIValidation\PSR7\Exception\NoPath;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidPath;

class OpenAPIRouteMismatchException extends Exception
{
    public static function fromMultipleOperationsMismatchForRequest(MultipleOperationsMismatchForRequest $exception): self
    {
        return new self($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
    }

    public static function fromNoPath(NoPath $exception): self
    {
        return new self(sprintf('[Route Schema Mismatch] Route path [%s] was not found on Schema.', $exception->path()));
    }

    public static function fromNoOperation(NoOperation $exception): self
    {
        return new self(sprintf('[Route Schema Mismatch] Route path [%s: %s] was not found on Schema.', strtoupper($exception->method()), $exception->path()));
    }

    public static function fromInvalidPath(InvalidPath $exception): self
    {
        $message = sprintf(
            "[Route Schema Mismatch] %s\n%s",
            $exception->getMessage(),
            $exception->getPrevious()?->getPrevious()?->getMessage() ?? '',
        );

        return new self($message);
    }
}
