<?php

namespace Studocu\AssertsSchemaForLaravel\Tests;

use Generator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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

    /**
     * @dataProvider exceptionExpectationDataProvider
     */
    public function testItShouldNotFailIfExceptionIsExpected(
        array $exceptions,
        callable $prepare,
        array $call,
        int $status,
    ): void {
        foreach ($exceptions as $exceptionMessage) {
            $this->expectsSchemaExceptionMessage($exceptionMessage);
        }

        $prepare();

        $response = $this->json(...$call);

        $response->assertStatus($status);
    }

    /**
     * @dataProvider exceptionExpectationDataProvider
     */
    public function testItShouldFailIfExceptionIsNotExpected(
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
        yield 'It should ignore if the method doesn\'t match (for development purposes)' => [
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

        yield 'Route method mismatch' => [
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
    }
}
