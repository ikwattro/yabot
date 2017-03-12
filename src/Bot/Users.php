<?php

namespace Nopolabs\Yabot\Bot;


use Slack\User;

class Users
{
    private $users = [];
    private $usersById = [];
    private $usersByName = [];

    /**
     * @param User[] $users
     */
    public function update(array $users)
    {
        $this->users = $users;
        foreach ($users as $index => $user) {
            $this->usersById[$user->getId()] = $index;
            $this->usersByName[$user->getUsername()] = $index;
        }
    }

    public function byId($id) : User
    {
        $index = $this->usersById[$id];
        return $this->users[$index];
    }

    public function byName($name) : User
    {
        $index = $this->usersByName[$name];
        return $this->users[$index];
    }
}
