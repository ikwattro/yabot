<?php

namespace Nopolabs\Yabot\Helpers;


use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

trait GuzzleTrait
{
    /** @var Client */
    private $guzzle;

    public function setGuzzle(Client $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    public function getGuzzle() : Client
    {
        return $this->guzzle;
    }

    public function getAsync($uri) : PromiseInterface
    {
        return $this->getGuzzle()->getAsync($uri);
    }

    public function post($uri, $options = []) : ResponseInterface
    {
        return $this->getGuzzle()->post($uri, $options);
    }
}