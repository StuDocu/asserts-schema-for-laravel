<?php

namespace Studocu\AssertsSchemaForLaravel\Exceptions;

use Exception;
use League\OpenAPIValidation\PSR7\Exception\NoOperation;
use League\OpenAPIValidation\PSR7\Exception\NoPath;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidBody;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidPath;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidQueryArgs;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidSecurity;
use League\OpenAPIValidation\Schema\Exception\KeywordMismatch;
use League\OpenAPIValidation\Schema\Exception\TypeMismatch;
use Studocu\AssertsSchemaForLaravel\Support\ExceptionParser;
use Studocu\AssertsSchemaForLaravel\Support\ExceptionRenderer;

class OpenAPIMismatchException extends Exception
{
    public static function from(Exception $exception, string $context = ''): self|Exception
    {
        /** @var array<array-key, ExceptionRenderer> $exceptionRenderers */
        $exceptionRenderers = [
            new ExceptionRenderer(TypeMismatch::class, function (TypeMismatch $exception): string {
                $crumb = ExceptionParser::getCrumb($exception);

                $message = "{$exception->getMessage()}.";

                if ($crumb) {
                    $message .= " Error in: \"{$crumb}\".";
                }

                if ($previous = $exception->getPrevious()) {
                    $message .= " {$previous->getMessage()}.";
                }

                // Append a dot at the end of the message if it is not there already
                if (! str_ends_with($message, '.')) {
                    $message .= '.';
                }

                // Replace single quotes with double quotes:
                return str_replace("'", '"', $message);
            }),
            new ExceptionRenderer(InvalidBody::class, function (InvalidBody $exception): string {
                $crumb = ExceptionParser::getCrumb($exception);

                $message = "{$exception->getMessage()}.";

                if ($crumb) {
                    $message .= " Error in: \"{$crumb}\".";
                }

                if ($previous = $exception->getPrevious()) {
                    $message .= " {$previous->getMessage()}";
                }

                // Append a dot at the end of the message if it is not there already
                if (! str_ends_with($message, '.')) {
                    $message .= '.';
                }

                // Replace single quotes with double quotes:
                return str_replace("'", '"', $message);
            }),
            new ExceptionRenderer(KeywordMismatch::class, function (KeywordMismatch $exception): string {
                $crumb = ExceptionParser::getCrumb($exception);

                $message = "{$exception->getMessage()}.";

                if ($crumb) {
                    $message .= " Error in: \"{$crumb}\".";
                }

                $message .= ' for keyword: '.$exception->keyword();

                if ($previous = $exception->getPrevious()) {
                    $message .= " {$previous->getMessage()}.";
                }

                // Append a dot at the end of the message if it is not there already
                if (! str_ends_with($message, '.')) {
                    $message .= '.';
                }

                // Replace single quotes with double quotes:
                return str_replace("'", '"', $message);
            }),
            new ExceptionRenderer(InvalidQueryArgs::class, fn (InvalidQueryArgs $exception): string => $exception->getMessage()),
            new ExceptionRenderer(InvalidPath::class, fn (InvalidPath $exception): string => sprintf(
                "%s\n%s",
                $exception->getMessage(),
                $exception->getPrevious()?->getPrevious()?->getMessage() ?? '',
            )),
            new ExceptionRenderer(NoOperation::class, fn (NoOperation $exception): string => sprintf('Route path [%s: %s] was not found on Schema.', strtoupper($exception->method()), $exception->path())),
            new ExceptionRenderer(NoPath::class, fn (NoPath $exception): string => sprintf('Route path [%s] was not found on Schema.', $exception->path())),
            new ExceptionRenderer(InvalidSecurity::class, fn (InvalidSecurity $exception): string => $exception->getMessage()),
        ];

        $context = $context ? ' '.ucfirst($context) : '';

        foreach ($exceptionRenderers as $renderer) {
            if ($exception instanceof $renderer->exception) {
                return new self("[OpenAPI{$context} Mismatch] {$renderer($exception)}", $exception->getCode(), $exception);
            }
        }

        return $exception;
    }

    /**
     * Special case.
     */
    public static function fromComponentName(string $componentName): self
    {
        return new self(sprintf('[Component Schema Mismatch] Component [%s] was not found on Schema.', $componentName));
    }
}
