# Yet Another Bot (yabot)

yabot is a slack chat bot written in php.

## Getting started

Bots are simple beasts. They listen and respond to messages.
In order for yabot to listen to messages in your slack rooms
you need to provide a token which you get from Slack. 
Please read https://get.slack.help/hc/en-us/articles/215770388 
for information on how to issue new authentication tokens.

You tell yabot about the token by placing it in config/config.xml.
This file is not in source control because the token is not meant 
to be shared publicly. In fact if you do happen to commit the token
to a public repo Slack will revoke it (I know from experience).
Copy config/config.example.xml to config/config.xml and add your
token here:

        <parameter key="slack.token">SLACK-TOKEN-HERE</parameter>

## Running yabot

    php yabot.php

## Plugins

Yabot uses plugins to know what to listen for and how to respond.

There are examples in src/Examples, src/Reservations, and src/Queue.

## Configuration

Yabot uses the Symfony dependency-injection container for configuration.
