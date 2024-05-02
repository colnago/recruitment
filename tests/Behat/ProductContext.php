<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

final class ProductContext implements Context
{
    private Response $response;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly RouterInterface $router,
    ) {
    }

    /**
     * @Then the response should be received with status code :code
     */
    public function theResponseShouldBeReceivedWithStatusCode(int $code): void
    {
        if (empty($this->response)) {
            throw new \RuntimeException('No response received');
        }

        Assert::same($this->response->getStatusCode(), $code);
    }

    /**
     * @When a user sends request to get all products
     */
    public function aUserSendsRequestToGetAllProducts(): void
    {
        $path = $this->router->generate('productList');
        $this->response = $this->kernel->handle(Request::create($path));
    }

    /**
     * @Given there should be :numberOfProducts products in response
     */
    public function thereShouldBeProductsInResponse(int $numberOfProducts): void
    {
        if (empty($this->response)) {
            throw new \RuntimeException('No response received');
        }

        /** @var array{data: mixed} $response */
        $response = json_decode($this->response->getContent() ?: '', true, JSON_THROW_ON_ERROR);
        Assert::isArray($response['data']);

        Assert::count($response['data'], $numberOfProducts);
    }
}
