<?php

namespace Ribedesign\Authorisation\Contracts;

interface Object
{
    /**
     * An object must be applied to permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions();

    /**
     * Find an object by its name.
     *
     * @param string $name
     *
     * @throws \Ribedesign\Authorisation\Exceptions\ObjectDoesNotExist
     *
     * @return Object
     */
    public static function findByName($name);
}
