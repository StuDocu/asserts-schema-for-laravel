<?php

namespace Studocu\AssertsSchemaForLaravel;

use cebe\openapi\Reader;
use cebe\openapi\spec\Schema;
use Exception;
use Illuminate\Support\Str;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use League\OpenAPIValidation\Schema\SchemaValidator;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Studocu\AssertsSchemaForLaravel\Exceptions\OpenAPIMismatchException;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class APISchemaValidator
{
    private string $apiPrefix;

    private ValidatorBuilder $validatorBuilder;

    private string $routeUri;

    public function __construct(
        private string $yamlFile,
        string $apiPrefix,
        private string $method,
        private string $uri,
        string $routeUri,
    ) {
        $this->apiPrefix = ltrim($apiPrefix, characters: '/');
        $this->routeUri = Str::replaceFirst($this->apiPrefix, '', subject: $routeUri);

        $this->validatorBuilder = (new ValidatorBuilder())->fromYamlFile($this->yamlFile);
    }

    /**
     * @param  array<array-key, mixed>  $parameters
     * @param  array<array-key, mixed>  $cookies
     * @param  array<array-key, mixed>  $files
     * @param  array<array-key, mixed>  $server
     * @param  string|null  $content
     */
    public function validateRequest(array $parameters = [], array $cookies = [], array $files = [], array $server = [], $content = null): void
    {
        $routedRequestValidator = $this->validatorBuilder->getRoutedRequestValidator();

        $address = new OperationAddress($this->routeUri, mb_strtolower($this->method));

        $request = $this->createRequest($parameters, $cookies, $files, $server, $content);

        try {
            $routedRequestValidator->validate($address, $request);
        } catch (Exception $exception) {
            throw OpenAPIMismatchException::from($exception, 'request');
        }

        // Generic Exceptions

        // Response Exceptions
    }

    public function validateResponse(Response $response): void
    {
        $psrResponse = $this->createPsrResponse($response);

        $address = new OperationAddress($this->routeUri, mb_strtolower($this->method));

        $responseValidator = $this->validatorBuilder->getResponseValidator();

        try {
            $responseValidator->validate($address, $psrResponse);
        } catch (Exception $exception) {
            throw OpenAPIMismatchException::from($exception, 'response');
        }
    }

    public function validateComponent(string $componentName, mixed $data): void
    {
        $spec = Reader::readFromYamlFile($this->yamlFile);

        if ($spec->components?->schemas === null) {
            throw OpenAPIMismatchException::fromComponentName($componentName);
        }

        if (! array_key_exists($componentName, $spec->components->schemas)) {
            throw OpenAPIMismatchException::fromComponentName($componentName);
        }

        /** @var Schema $component */
        $component = $spec->components->schemas[$componentName];

        try {
            (new SchemaValidator())->validate($data, $component);
        } catch (Exception $exception) {
            throw OpenAPIMismatchException::from($exception);
        }
    }

    /**
     * @param  array<array-key, mixed>  $parameters
     * @param  array<array-key, mixed>  $cookies
     * @param  array<array-key, mixed>  $files
     * @param  array<array-key, mixed>  $server
     * @param  string|null  $content
     */
    private function createRequest(array $parameters, array $cookies, array $files, array $server, $content): ServerRequestInterface
    {
        $request = Request::create($this->uri, $this->method, $parameters, $cookies, $files, $server, $content);

        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        return $psrHttpFactory->createRequest($request);
    }

    private function createPsrResponse(Response $response): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        return $psrHttpFactory->createResponse($response);
    }
}
