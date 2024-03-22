<?php

namespace Studocu\AssertsSchemaForLaravel\Support;

use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidBody;
use League\OpenAPIValidation\Schema\Exception\KeywordMismatch;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;

/**
 * Random helpers
 */
class ExceptionParser
{
    public static function getCrumb(KeywordMismatch|InvalidBody $exception): ?string
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
