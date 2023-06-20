<?php

namespace Studocu\AssertsSchemaForLaravel\Exceptions;

use Exception;
use League\OpenAPIValidation\Schema\Exception\KeywordMismatch;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;

class OpenAPIComponentMismatchException extends Exception
{
    public static function fromInvalidKeyword(KeywordMismatch $exception): self
    {
        $crumb = self::getCrumb($exception);

        $message = "{$exception->getMessage()}\n";

        if ($crumb) {
            $message .= "Error in: \"$crumb\". ";
        }

        $message .= ' for keyword: '.$exception->keyword().'. '.$exception->getPrevious()?->getMessage();

        return new self($message, $exception->getCode(), $exception);
    }

    private static function getCrumb(KeywordMismatch $exception): ?string
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
}
