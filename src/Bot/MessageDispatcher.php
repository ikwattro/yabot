<?php

namespace Nopolabs\Yabot\Bot;


use Exception;
use Psr\Log\LoggerInterface;

class MessageDispatcher
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function dispatch($object, Message $message, array $matchers)
    {
        foreach ($matchers as $name => $params) {

            $matched = $this->matchMessage($message, $name, $params);

            if ($matched === false) {
                continue;
            }

            $this->dispatchMessage($object, $message, $matched);

            if ($message->isHandled()) {
                return;
            }
        }
    }

    protected function matchMessage(Message $message, $name, array $params)
    {
        $params = is_array($params) ? $params : ['pattern' => $params];

        if (isset($params['enabled']) && !$params['enabled']) {
            return false;
        }
        if (isset($params['disabled']) && $params['disabled']) {
            return false;
        }
        if (isset($params['channel']) && !$message->matchesChannel($params['channel'])) {
            return false;
        }
        if (isset($params['user']) && !$message->matchesUser($params['user'])) {
            return false;
        }
        if (isset($params['pattern'])) {
            $matches = $message->matchPattern($params['pattern']);
            if ($matches === false) {
                return false;
            }
        } else {
            $matches = [];
        }

        $this->logger->info("matched: $name");

        $method = isset($params['method']) ? $params['method'] : $name;

        return [$method, $matches];
    }

    protected function dispatchMessage($object, Message $message, array $matched)
    {
        list($method, $matches) = $matched;

        try {
            call_user_func([$object, $method], $message, $matches);
        } catch (Exception $e) {
            $this->logger->warning('Exception in '.static::class.'::'.$method);
            $this->logger->warning($e->getMessage());
            $this->logger->warning($e->getTraceAsString());
        }
    }
}