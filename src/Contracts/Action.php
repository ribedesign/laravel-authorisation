<?php

namespace Ribedesign\Authorisation\Contracts;

interface Action
{
    /**
     * An action must be applied to permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions();

    /**
     * Find an action by its name.
     *
     * @param string $name
     *
     * @throws \Ribedesign\Authorisation\Exceptions\ActionDoesNotExist
     *
     * @return Action
     */
    public static function findByName($name);
}
