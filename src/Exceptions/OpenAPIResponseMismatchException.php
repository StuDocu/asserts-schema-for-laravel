<?php

namespace Studocu\AssertsSchemaForLaravel\Exceptions;

use Exception;
use League\OpenAPIValidation\PSR7\Exception\NoResponseCode;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidBody;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;

class OpenAPIResponseMismatchException extends Exception
{
    public static function fromInvalidBody(InvalidBody $exception): self
    {
        $crumb = self::getCrumb($exception);

        $message = "{$exception->getMessage()}\n";

        if ($crumb) {
            $message .= "Error in: \"{$crumb}\". ";
        }

        $message .= "{$exception->getPrevious()?->getMessage()}";

        return new self($message, $exception->getCode(), $exception);
    }

    private static function getCrumb(InvalidBody $exception): ?string
    {
        $previous = $exception->getPrevious();

        if (! $previous instanceof SchemaMismatch) {
            return null;

        }

        $chain = $previous->dataBreadCrumb()?->buildChain() ?? null;

        if (! $chain) {
            return null;
        }

        return implode('.', $chain);
    }

    public static function fromNoResponseCode(NoResponseCode $exception): self
    {
        $method = strtoupper($exception->method());

        return new self("[Response Schema Mismatch] Response code {$exception->responseCode()} for [{$method}] {$exception->path()} was not found.", $exception->getCode(), $exception);
    }
}
