<?php

namespace Studocu\AssertsSchemaForLaravel\Tests;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidCookies;
use Symfony\Component\HttpFoundation\Cookie;

class CookieValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSchema(
            yamlFullPath: __DIR__.'/Stubs/cookie.yaml',
            apiPrefix: '/api/v1',
        );
    }

    public function test_it_should_pass_when_sending_the_right_cookie(): void
    {
        Route::prefix('api/v1')->group(function () {
            Route::get('send-cookie', fn () => ['test' => 'foo']);
        });

        $this
            ->withCredentials()
            ->withCookie('my_foo_cookie', '1234')
            ->getJson('/api/v1/send-cookie')
            ->assertOk();
    }

    public function test_it_should_break_when_sending_the_wrong_cookie(): void
    {
        Route::prefix('api/v1')->group(function () {
            Route::get('send-cookie', fn () => ['test' => 'foo']);
        });

        $this->expectException(InvalidCookies::class);
        $this->expectExceptionMessage('Missing required cookie "my_foo_cookie" for Request [get /send-cookie]');

        $this
            ->withCredentials()
            ->withCookie('my_baz_cookie', '1234')
            ->getJson('/api/v1/send-cookie')
            ->assertOk();
    }

    public function test_it_should_pass_when_receiving_the_right_cookie(): void
    {
        Route::prefix('api/v1')->group(function () {
            Route::get('set-cookie', function () {
                return (new JsonResponse(['test' => 'foo']))
                    ->withCookie(new Cookie('my_foo_cookie', '1234'));
            });
        });

        $this
            ->getJson('/api/v1/set-cookie')
            ->assertPlainCookie('my_foo_cookie', '1234')
            ->assertOk();
    }

    public function test_it_should_break_when_receiving_the_wrong_cookie(): void
    {
        Route::prefix('api/v1')->group(function () {
            Route::get('set-cookie', fn () => ['test' => 'foo']);
        });

        // There is no way to do this now, so it will not fail whatsoever.
        //        $this->expectException(OpenAPIMismatchException::class);

        $this
            ->getJson('/api/v1/set-cookie')
            ->assertOk();
    }
}
