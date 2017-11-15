<?php

namespace Ribedesign\Authorisation\Models;

use Illuminate\Database\Eloquent\Model;
use Ribedesign\Authorisation\Traits\HasPermissions;
use Ribedesign\Authorisation\Exceptions\RoleDoesNotExist;
use Ribedesign\Authorisation\Contracts\Role as RoleContract;
use Ribedesign\Authorisation\Traits\RefreshesAuthorisationCache;

/**
 * Class Role
 * @package Ribedesign\Authorisation\Models
 */
class Role extends Model implements RoleContract
{
    use HasPermissions;
    use RefreshesAuthorisationCache;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    public $guarded = ['id'];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('laravel-authorisation.table_names.roles'));
    }

    /**
     * A role may be given various permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(
            config('laravel-authorisation.models.permission'),
            config('laravel-authorisation.table_names.role_has_permissions')
        );
    }

    /**
     * A role may be assigned to various users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(
            config('auth.model') ?: config('auth.providers.users.model'),
            config('laravel-authorisation.table_names.user_has_roles')
        );
    }

    /**
     * A role can have een parent role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentrole()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Find a role by its name.
     *
     * @param string $name
     *
     * @throws RoleDoesNotExist
     *
     * @return Role
     */
    public static function findByName($name)
    {
        $role = static::where('name', $name)->first();

        if (!$role) {
            throw new RoleDoesNotExist();
        }

        return $role;
    }

    /**
     * Determine if the user may perform the given permission.
     *
     * @param string|Permission $permission
     *
     * @return bool
     */
    public function hasPermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = app(Permission::class)->findByName($permission);
        }

        return $this->permissions->contains('id', $permission->id);
    }

    /**
     * Get all parent roles.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getParentRoles()
    {
        $parents = collect([]);

        $parent = $this->parent;

        while (!is_null($parent)) {
            $parents->push($parent);
            $parent = $parent->parent;
        }

        return $parents;
    }

    /**
     * Get permissions from all parent roles.
     *
     * @return \Illuminate\Support\Collection
     */
    public function parentPermissions()
    {
        $parentPermissions = collect([]);

        $parent = $this->parentrole;

        while (!is_null($parent)) {
            if ($parent->permissions->count()) {
                $parentPermissions->push($parent->permissions);
            }
            $parent = $parent->parentrole;
        }

        return $parentPermissions;
    }

    /**
     * Determine if the user may perform the given permission for any parent role.
     *
     * @param string|Permission $permission
     *
     * @return bool
     */
    public function parentsHavePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = app(Permission::class)->findByName($permission);
        }

        $parent = $this->parent;
        while (!is_null($parent)) {
            if ($parent->permissions->contains('id', $permission->id)) {
                return true;
            }
            $parent = $parent->parent;
        }
        return false;
    }
}
