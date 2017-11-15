<?php

namespace Ribedesign\Authorisation\Contracts;

interface Role
{
    /**
     * A role may be given various permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions();

    /**
     * A role may be assigned to various users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users();

    /**
     * A role can have een parent role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentrole();

    /**
     * Find a role by its name.
     *
     * @param string $name
     *
     * @throws RoleDoesNotExist
     */
    public static function findByName($name);

    /**
     * Determine if the user may perform the given permission.
     *
     * @param string|Permission $permission
     *
     * @return bool
     */
    public function hasPermissionTo($permission);

    /**
     * Get all parent roles.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getParentRoles();

    /**
     * Get permissions from all parent roles.
     *
     * @return \Illuminate\Support\Collection
     */
    public function parentPermissions();

    /**
     * Determine if the user may perform the given permission for any parent role.
     *
     * @param string|Permission $permission
     *
     * @return bool
     */
    public function parentsHavePermissionTo($permission);
}
