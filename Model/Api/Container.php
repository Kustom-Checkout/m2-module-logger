<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */
declare(strict_types=1);

namespace Klarna\Logger\Model\Api;

/**
 * @internal
 */
class Container
{

    public const DEFAULT_STATUS = 200;

    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    private $method;
    /**
     * @var string
     */
    private $action;
    /**
     * @var array
     */
    private $request;
    /**
     * @var array
     */
    private $response;
    /**
     * @var string
     */
    private $klarnaId;
    /**
     * @var string
     */
    private $service;
    /**
     * @var string|null
     */
    private $incrementId;

    /**
     * Getting back the url
     *
     * @return string
     */
    public function getUrl(): string
    {
        return (string) $this->url;
    }

    /**
     * Setting the url
     *
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * Getting back the method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return (string) $this->method;
    }

    /**
     * Setting the method
     *
     * @param string $method
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * Getting back the action
     *
     * @return string
     */
    public function getAction(): string
    {
        return (string) $this->action;
    }

    /**
     * Setting the action
     *
     * @param string $action
     */
    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    /**
     * Getting back the request
     *
     * @return array
     */
    public function getRequest(): array
    {
        return $this->request;
    }

    /**
     * Setting the request
     *
     * @param array|null $request
     */
    public function setRequest(?array $request = []): void
    {
        $this->request = $request ?? [];
    }

    /**
     * Getting back the response
     *
     * @return array
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    /**
     * Setting the response
     *
     * @param array $response
     */
    public function setResponse(array $response): void
    {
        $this->response = $response;
    }

    /**
     * Getting back the Klarna ID
     *
     * @return string|null
     */
    public function getKlarnaId(): ?string
    {
        return $this->klarnaId;
    }

    /**
     * Setting the Klarna ID
     *
     * @param string|null $klarnaId
     */
    public function setKlarnaId(?string $klarnaId): void
    {
        $this->klarnaId = $klarnaId;
    }

    /**
     * Getting back the service
     *
     * @return string
     */
    public function getService(): string
    {
        return (string) $this->service;
    }

    /**
     * Setting the service
     *
     * @param string $service
     */
    public function setService(string $service): void
    {
        $this->service = $service;
    }

    /**
     * Getting back the increment id
     *
     * @return string|null
     */
    public function getIncrementId(): ?string
    {
        return $this->incrementId;
    }

    /**
     * Setting the increment ID
     *
     * @param string|null $incrementId
     */
    public function setIncrementId(?string $incrementId): void
    {
        $this->incrementId = $incrementId;
    }

    /**
     * Getting the content of the container
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'method' => $this->method,
            'action' => $this->action,
            'request' => $this->request,
            'response' => $this->response,
            'klarnaId' => $this->klarnaId,
            'service' => $this->service,
            'incrementId' => $this->incrementId
        ];
    }
}
