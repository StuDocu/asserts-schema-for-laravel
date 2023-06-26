<?php

namespace Studocu\AssertsSchemaForLaravel\Concerns;

use Illuminate\Testing\TestResponse;
use Studocu\AssertsSchemaForLaravel\APISchemaValidator;

/**
 * @mixin \Illuminate\Foundation\Testing\Concerns\MakesHttpRequests
 */
trait AssertsSchema
{
    protected string $yamlPath;

    protected string $apiPrefix;

    protected bool $shouldValidateSchema = true;

    public function configureSchema(string $yamlFullPath, string $apiPrefix): void
    {
        $this->yamlPath = $yamlFullPath;
        $this->apiPrefix = $apiPrefix;
    }

    public function dontValidateNextCall(): void
    {
        $this->shouldValidateSchema = false;
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array<array-key, mixed>  $parameters
     * @param  array<array-key, mixed>  $cookies
     * @param  array<mixed>  $files
     * @param  array<array-key, mixed>  $server
     * @param  string|null  $content
     * @return TestResponse
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        if (! $this->shouldValidateSchema) {
            $this->shouldValidateSchema = true;

            return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
        }

        $apiValidator = new APISchemaValidator(
            yamlFile: $this->yamlPath,
            apiPrefix: $this->apiPrefix,
            method: $method,
            uri: $uri,
            routeUri: $this->getRouteUri($uri, $method),
        );

        $apiValidator->validateRequest(
            parameters: $parameters,
            cookies: $cookies,
            files: array_merge($files, $this->extractFilesFromDataArray($parameters)),
            server: array_replace($this->serverVariables, $server),
            content: $content,
        );

        $response = parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);

        if ($this->shouldSkipResponseValidation($response)) {
            return $response;
        }

        $apiValidator->validateResponse($response->baseResponse);

        return $response;
    }

    public function validateComponent(string $componentName, mixed $data): bool
    {
        $apiValidator = new APISchemaValidator(
            yamlFile: $this->yamlPath,
            apiPrefix: $this->apiPrefix,
            method: 'GET',
            uri: '',
            routeUri: '',
        );

        $apiValidator->validateComponent($componentName, $data);

        return true;
    }

    private function shouldSkipResponseValidation(TestResponse $response): bool
    {
        return $response->getStatusCode() >= 500 || $response->getStatusCode() === 204;
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function getRouteUri(string $uri, string $method): string
    {
        return $this->app->make('router')->getRoutes()->match(
            $this->app->make('request')->create($uri, $method)
        )->uri();
    }
}
