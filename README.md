# Yet Another Bot (yabot)

yabot is a slack chat bot written in php.

## Getting started

Bots are simple beasts. They listen and respond to messages.
In order for yabot to listen to messages in your slack rooms
you need to provide a token which you get from Slack. 
Please read [Slack's documentation](https://get.slack.help/hc/en-us/articles/215770388)
for information on how to issue new authentication tokens.

    composer init
    composer require nopolabs/yabot
    mkdir config
    cp vendor/nopolabs/yabot/yabot.php yabot.php
    cp vendor/nopolabs/yabot/config/plugins.yml config/plugins.yml
    cp vendor/nopolabs/yabot/config.example.php config.php
    
Edit config.php and add your Slack API token:

    'slack.token' => 'SLACK-TOKEN-GOES-HERE',

*Do not save config.php in a public repository.* 
The slack.token is *not* meant to be shared publicly. 
If you do happen to commit the token to a public repo 
Slack will revoke it (I know from experience).

## Running yabot

    php yabot.php
    
## Configuration

Yabot uses a [Symfony dependency-injection](http://symfony.com/doc/current/components/dependency_injection.html)
container for configuration.

`yabot.php` loads three configuration files: 
`vendor/nopolabs/yabot/config/yabot.xml`, 
`config/plugins.yml`, and `config.php`.

`vendor/nopolabs/yabot/config/yabot.xml` defines core services used by
Yabot and available for plugins to get from the container. You should
not need to modify this file.

`config/plugins.yml` provides a place to configure plugins and shared
services for your Yabot application. See the discussion of 
[plugins](#plugins) below.

`config.php` provides a place to set or override runtime settings.

[Importing configuration files](http://symfony.com/doc/current/service_container/import.html)


## Logging

Logging is configured in config.php:

    'log.file' => 'logs/bot.log',
    'log.name' => 'bot',
    'log.level' => 'DEBUG',

## Plugins <a name="plugins"></a>

TODO:
* configuring and adding plugins to Yabot
* YabotContainer and how to add plugins to Yabot

Yabot uses plugins to know what to listen for and how to respond.

There are examples in `src/Examples`, `src/Reservations`, and `src/Queue`.

Minimally a plugin must implement `Nopolabs\Yabot\Bot\PluginInterface`:

    interface PluginInterface
    {
        public function onMessage(MessageInterface $message);
    }

This discussion will focus on how to use the default Plugin 
implementation provided by `Nopolabs\Yabot\Bot\PluginTrait`.

`PluginTrait` configures a `MessageDispatcher` and calls `MessageDispatcher::dispatch()`
with each received message:

    public function onMessage(MessageInterface $message)
    {
        $this->dispatcher->dispatch($this, $message);
    }

`MessageDispatcher` is configured with a set of matchers which it applies to each message.

Matcher syntax:

    // canonical:
    'matcherName' => [
        'pattern' => "/^help (?'topic'\\w+)\\b/", // optional, pattern applied by preg_match()
        'channel' => 'general',                   // optional, may be string or aray of strings
        'user' => 'dan',                          // optional, may be string or aray of strings
        'method' => 'help',                       // optional, will use matcherName if not set
    ],


`PluginTrait` provides some functions to help build matchers.

`expandMatchers` will expand shorthand notation:

    // shorthand:
    'help' => "/^help (?'topic'\\w+)\\b/",
    
    // expanded to:
    'help' => [
        'pattern => "/^help (?'topic'\\w+)\\b/",
        'method' => 'help',
    ],

`addToMatchers` will add a key/value pair to each matcher, e.g. to set 'channel':

    $matchers = $this->addToMatchers('channel', $channel, $matchers);

`replaceInPatterns` will perform string replacement on patterns 
(e.g. replaces ' ' with '\s+'):

    $matchers = $this->replaceInPatterns(' ', "\\s+", $matchers);

If the matcher matches `MessageDispatcher` calls the method on the plugin object 
with the message and any fields captured by the matcher pattern:

    call_user_func([$plugin, $method], $message, $matches);

## Responding to a Message

    // assuming: 'help' => "/^help (?'topic'\\w+)\\b/"
    public function help(MessageInterface $msg, array $matches)
    {
        $topic = $matches[1];
        $msg->reply("you want help with $topic");
    }

## Users and Channels

Slack messages use ids to reference users and channels, e.g.:

    Why not join <#C024BE7LR>?

    Hey <@U024BE7LH>, did you see my file?

`SlackClient` manages Users and Channels objects and provides methods to help 
map user and channel names to ids and ids to names.

## Message formatting and attachments

Yabot uses the Slack REST API to post messages because the web socket API
doesn't support formatting and attachments. See:
[Slack API Messages](https://api.slack.com/docs/messages)

