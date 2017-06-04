<?php

namespace Nopolabs\Yabot\Plugin;

use Exception;
use Nopolabs\Yabot\Helpers\ConfigTrait;
use Nopolabs\Yabot\Helpers\LogTrait;
use Nopolabs\Yabot\Message\Message;
use Throwable;

trait PluginTrait
{
    use LogTrait;
    use ConfigTrait;

    private $pluginId;

    /** @var PluginMatcher */
    private $pluginMatcher;

    /** @var MethodMatcher[] */
    private $methodMatchers;

    public function help(): string
    {
        return $this->get('help', 'no help available');
    }

    public function status(): string
    {
        return 'running';
    }

    public function init(string $pluginId, array $params)
    {
        $this->pluginId = $pluginId;
        $this->overrideConfig($params);
        $this->methodMatchers = $this->buildMethodMatchers($this->get('matchers'));
        $this->pluginMatcher = new PluginMatcher($pluginId, $this->getIsBot(), $this->getChannels(), $this->getUsers(), $this->getLog());

        $this->info("inited $pluginId config:", $this->getConfig());
    }

    public function replaceInPatterns($search, $replace, array $matchers): array
    {
        $replaced = [];
        foreach ($matchers as $name => $params) {
            $params['patterns'] = array_map(function($pattern) use ($search, $replace) {
                return str_replace($search, $replace, $pattern);
            }, $params['patterns']);
            $replaced[$name] = $params;
        }
        return $replaced;
    }

    public function getPriority(): int
    {
        return $this->get('priority');
    }

    public function getPrefix(): string
    {
        return $this->get('prefix');
    }

    public function handle(Message $message)
    {
        if (!$this->pluginMatch($message)) {
            return;
        }

        if ($matched = $this->methodMatch($message)) {
            list($method, $matches) = $matched;

            $this->dispatch($method, $message, $matches);
        }
    }

    protected function pluginMatch(Message $message): bool
    {
        return $this->pluginMatcher->matches($message);
    }

    protected function methodMatch(Message $message): array
    {
        foreach ($this->methodMatchers as $methodMatcher) {
            if (($matches = $methodMatcher->matches($message)) === false) {
                continue;
            }

            $method = $methodMatcher->getMethod();

            $this->info("{$this->pluginId}:{$methodMatcher->getName()}:$method matched");

            return [$method, $matches];
        }

        return [];
    }

    protected function dispatch(string $method, Message $message, array $matches)
    {
        if (!method_exists($this, $method)) {
            $this->warning("{$this->pluginId} no method named: $method");
            return;
        }

        try {

            $this->$method($message, $matches);

        } catch (Throwable $throwable) {
            $errmsg = 'Exception in '.static::class.'::'.$method."\n"
                .$throwable->getMessage()."\n"
                .$throwable->getTraceAsString();
            $this->warning($errmsg);
        }
    }

    protected function overrideConfig(array $params)
    {
        $config = $this->canonicalConfig(array_merge($this->getConfig(), $params));

        $this->setConfig($config);
    }

    protected function setPluginId($pluginId)
    {
        $this->pluginId = $pluginId;
    }

    protected function getUsers()
    {
        return $this->get('users');
    }

    protected function getChannels()
    {
        return $this->get('channels');
    }

    /**
     * @return bool|null
     */
    protected function getIsBot()
    {
        return $this->get('isBot');
    }

    protected function getMatchers(): array
    {
        return $this->get('matchers');
    }

    protected function canonicalConfig(array $config): array
    {
        return [
            'priority' => $config['priority'] ?? PluginManager::DEFAULT_PRIORITY,
            'prefix' => ($config['prefix'] ?? '') ? $config['prefix'] : PluginManager::NO_PREFIX,
            'isBot' => $config['isBot'] ?? null,
            'channels' => $config['channels'] ?? array_filter([$config['channel'] ?? null]),
            'users' => $config['users'] ?? array_filter([$config['user'] ?? null]),
            'matchers' => $this->canonicalMatchers($config['matchers'] ?? []),
        ];
    }

    protected function canonicalMatchers(array $matchers): array
    {
        $expanded = [];

        foreach ($matchers as $name => $params) {
            $expanded[$name] = $this->canonicalMatcher($name, $params);
        }

        if (empty($expanded)) {
            $this->warning("{$this->pluginId} has no matchers");
        }

        return $expanded;
    }

    protected function canonicalMatcher(string $name, $params) : array
    {
        $params = is_array($params) ? $params : ['patterns' => [$params]];

        $method = $params['method'] ?? $name;

        if (!method_exists($this, $method)) {
            $this->warning("{$this->pluginId} no method named: $method");
        }

        return [
            'isBot' => $params['isBot'] ?? null,
            'channels' => $params['channels'] ?? array_filter([$params['channel'] ?? null]),
            'users' => $params['users'] ?? array_filter([$params['user'] ?? null]),
            'patterns' => $params['patterns'] ?? array_filter([$params['pattern'] ?? null]),
            'method' => $params['method'] ?? $name,
        ];
    }

    protected function buildMethodMatchers(array $matchers) : array
    {
        $methodMatchers = [];

        foreach ($matchers as $name => $params) {
            $methodMatchers[] = new MethodMatcher(
                $name,
                $params['isBot'],
                $params['channels'],
                $params['users'],
                $this->validPatterns($params['patterns'], $name),
                $params['method'],
                $this->getLog()
            );
        }

        return $methodMatchers;
    }

    protected function validPatterns(array $patterns, $name) : array
    {
        $valid = [];
        foreach ($patterns as $pattern) {
            if ($this->isValidRegExp($pattern, $name)) {
                $valid[] = $pattern;
            }
        }
        return $valid;
    }

    protected function isValidRegExp($pattern, $name) : bool
    {
        try {
            preg_match($pattern, '');
            return true;
        } catch (Throwable $e) {
            $this->warning("$name.pattern='$pattern' ".$e->getMessage());
            return false;
        }
    }
}