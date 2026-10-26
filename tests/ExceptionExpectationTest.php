<?php

namespace Studocu\AssertsSchemaForLaravel\Tests;

use Generator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Studocu\AssertsSchemaForLaravel\Exceptions\OpenAPIMismatchException;

class ExceptionExpectationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSchema(
            yamlFullPath: __DIR__.'/Stubs/api.yaml',
            apiPrefix: '/api/v1',
        );
    }

    #[DataProvider('exceptionExpectationDataProvider')]
    public function test_it_should_not_fail_if_exception_is_expected(
        array $exceptions,
        callable $prepare,
        array $call,
        int $status,
    ): void {
        $this->expectsSchemaExceptionMessage($exceptions);

        $prepare();

        $response = $this->json(...$call);

        $response->assertStatus($status);
    }

    #[DataProvider('exceptionExpectationDataProvider')]
    public function test_it_should_fail_if_exception_is_not_expected(
        array $exceptions,
        callable $prepare,
        array $call,
        int $status,
    ): void {
        $exceptionMessage = $exceptions[0];

        $this->expectException(OpenAPIMismatchException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $prepare();

        $this->json(...$call);
    }

    public static function exceptionExpectationDataProvider(): Generator
    {
        yield 'It should ignore if the method doesn\'t match' => [
            'exceptions' => [
                '[OpenAPI Request Mismatch] Route path [POST: /test] was not found on Schema.',
                '[OpenAPI Response Mismatch] Route path [POST: /test] was not found on Schema.',
            ],
            'prepare' => fn () => Route::post('/api/v1/test', fn () => response()->json(['id' => 1], 200)),
            'call' => [
                'method' => 'POST',
                'uri' => '/api/v1/test',
                'data' => [],
                'headers' => [],
            ],
            'status' => 200,
        ];

        yield 'It should allow skipping the body validation' => [
            'exceptions' => [
                '[OpenAPI Request Mismatch] Body does not match schema for content-type "application/json" for Request [post /my-url/{id}]. Error in: "foo". '
                 .'Keyword validation failed: Required property "foo" must be present in the object.',
            ],
            'prepare' => fn () => Route::post('/api/v1/my-url/{id}', function (Request $request) {
                $request->validate([
                    'foo' => 'required',
                    'bar' => 'integer',
                ]);

                return response()->json(['id' => 1, 'message' => 'ok'], 200);
            }),
            'call' => [
                'method' => 'POST',
                'uri' => '/api/v1/my-url/1',
                'data' => ['bar' => 1],
                'headers' => [],
            ],
            'status' => 422,
        ];

        yield 'None of security schemas match' => [
            'exceptions' => [
                '[OpenAPI Request Mismatch] None of security schemas did match for Request [get /auth]',
            ],
            'prepare' => fn () => Route::get('/api/v1/auth', fn () => Gate::allowIf(false)),
            'call' => [
                'method' => 'GET',
                'uri' => '/api/v1/auth',
                'data' => [],
                'headers' => [],
            ],
            'status' => 403,
        ];
    }
}
