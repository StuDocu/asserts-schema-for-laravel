<?php

namespace Studocu\AssertsSchemaForLaravel\Support;

use Closure;
use Exception;

/**
 * DTO class.
 *
 * @template T of Exception
 */
class ExceptionRenderer
{
    /**
     * @param  class-string<T>  $exception
     * @param  Closure(T): string  $renderer
     */
    public function __construct(
        public readonly string $exception,
        private readonly Closure $renderer,
    ) {}

    /**
     * @param  T  $exception
     */
    public function __invoke(Exception $exception): string
    {
        return ($this->renderer)($exception);
    }
}
