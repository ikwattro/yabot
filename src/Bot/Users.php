<?php

namespace Nopolabs\Yabot\Bot;


class Users extends AbstractIdNameMap
{
    protected function getId($thing): string
    {
        return $thing->getId();
    }

    protected function getName($thing): string
    {
        return $thing->getUsername();
    }
}
