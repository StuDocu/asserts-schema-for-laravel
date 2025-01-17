<?php

namespace Studocu\AssertsSchemaForLaravel\Support;

class ExceptionExpectationManager
{
    /**
     * @param  array<string>  $expectedExceptions
     */
    public function __construct(
        private array $expectedExceptions = [],
    ) {}

    public function expects(string $exceptionMessage): void
    {
        $this->expectedExceptions[] = $exceptionMessage;
    }

    /**
     * @template T
     *
     * @param  callable(): T  $function
     * @return T|null
     */
    public function call(callable $function): mixed
    {
        try {
            return $function();
        } catch (\Throwable $exception) {
            if (in_array($exception->getMessage(), $this->expectedExceptions)) {
                // Remove only the first occurrence of the exception message from the expected exceptions.
                // Don't use array_diff since that one will replace all ocurrences.
                $this->removeException($exception->getMessage());

                return null;
            }

            throw $exception;
        }
    }

    public function assertExceptionsWereMet(): void
    {
        if (! empty($this->expectedExceptions)) {
            throw new \Exception('Expected exceptions were not met: '.implode(', ', $this->expectedExceptions));
        }
    }

    private function removeException(string $exception): void
    {
        $key = array_search($exception, $this->expectedExceptions);
        if ($key !== false) {
            unset($this->expectedExceptions[$key]);
        }
        $this->expectedExceptions = array_values($this->expectedExceptions); // Re-index the array
    }
}
