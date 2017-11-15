<?php

namespace Ribedesign\Authorisation\Traits;

use Illuminate\Support\Collection;
use Ribedesign\Authorisation\Contracts\Role;
use Ribedesign\Authorisation\Contracts\Permission;

trait HasRoles
{
    use HasPermissions;
    use RefreshesAuthorisationCache;

    /**
     * A user may have multiple roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(
            config('laravel-authorisation.models.role'),
            config('laravel-authorisation.table_names.user_has_roles')
        );
    }

    /**
     * A user may have multiple direct permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(
            config('laravel-authorisation.models.permission'),
            config('laravel-authorisation.table_names.user_has_permissions')
        );
    }

    /**
     * Scope the user query to certain roles only.
     *
     * @param string|array|Role|\Illuminate\Support\Collection $roles
     *
     * @return bool
     */
    public function scopeRole($query, $roles)
    {
        if ($roles instanceof Collection) {
            $roles = $roles->toArray();
        }

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        $roles = array_map(function ($role) {
            if ($role instanceof Role) {
                return $role;
            }

            return app(Role::class)->findByName($role);
        }, $roles);

        return $query->whereHas('roles', function ($query) use ($roles) {
            $query->where(function ($query) use ($roles) {
                foreach ($roles as $role) {
                    $query->orWhere(config('laravel-authorisation.table_names.roles') . '.id', $role->id);
                }
            });
        });
    }

    /**
     * Assign the given role to the user.
     *
     * @param array|string|\Ribedesign\Authorisation\Models\Role ...$roles
     *
     * @return \Ribedesign\Authorisation\Contracts\Role
     */
    public function assignRole(...$roles)
    {
        $roles = collect($roles)
            ->flatten()
            ->map(function ($role) {
                return $this->getStoredRole($role);
            })
            ->all();

        $this->roles()->saveMany($roles);

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Revoke the given role from the user.
     *
     * @param string|Role $role
     */
    public function removeRole($role)
    {
        $this->roles()->detach($this->getStoredRole($role));
    }

    /**
     * Remove all current roles and set the given ones.
     *
     * @param array ...$roles
     *
     * @return $this
     */
    public function syncRoles(...$roles)
    {
        $this->roles()->detach();

        return $this->assignRole($roles);
    }

    /**
     * Determine if the user has (one of) the given role(s).
     *
     * @param string|array|Role|\Illuminate\Support\Collection $roles
     *
     * @return bool
     */
    public function hasRole($roles)
    {
        $allroles = new Collection();
        if ($this->roles instanceof Collection) {
            foreach ($this->roles as $role) {
                $parent = $role->parentrole;
                while (!is_null($parent)) {
                    $allroles->push($parent);
                    $parent = $parent->parentrole;
                }
            }
            $allroles = $allroles->merge($this->roles)->unique('id')->values();
        }

        if (is_string($roles)) {
            return $allroles->contains('name', $roles);
        }

        if ($roles instanceof Role) {
            return $allroles->contains('id', $roles->id);
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }

            return false;
        }

        return (bool)$roles->intersect($allroles)->count();
    }

    /**
     * Determine if the user has any of the given role(s).
     *
     * @param string|array|Role|\Illuminate\Support\Collection $roles
     *
     * @return bool
     */
    public function hasAnyRole($roles)
    {
        return $this->hasRole($roles);
    }

    /**
     * Determine if the user has all of the given role(s).
     *
     * @param string|Role|\Illuminate\Support\Collection $roles
     *
     * @return bool
     */
    public function hasAllRoles($roles)
    {
        $allroles = new Collection();
        if ($this->roles instanceof Collection) {
            foreach ($this->roles as $role) {
                $parent = $role->parentrole;
                while (!is_null($parent)) {
                    $allroles->push($parent);
                    $parent = $parent->parentrole;
                }
            }
            $allroles = $allroles->merge($this->roles)->unique('id')->values();
        }

        if (is_string($roles)) {
            return $allroles->contains('name', $roles);
        }

        if ($roles instanceof Role) {
            return $allroles->contains('id', $roles->id);
        }

        $roles = collect()->make($roles)->map(function ($role) {
            return $role instanceof Role ? $role->name : $role;
        });

        return $roles->intersect($allroles->pluck('name')) == $roles;
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

        return $this->hasDirectPermission($permission) || $this->hasPermissionViaRole($permission);
    }

    /**
     * Determine if the user has any of the given permissions.
     *
     * @param array ...$permissions
     *
     * @return bool
     */
    public function hasAnyPermission(...$permissions)
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @deprecated deprecated since version 1.0.1, use hasPermissionTo instead
     *
     * Determine if the user may perform the given permission.
     *
     * @param Permission $permission
     *
     * @return bool
     */
    public function hasPermission($permission)
    {
        return $this->hasPermissionTo($permission);
    }

    /**
     * Determine if the user has, via roles, the given permission.
     *
     * @param Permission $permission
     *
     * @return bool
     */
    protected function hasPermissionViaRole(Permission $permission)
    {
        return $this->hasRole($permission->roles);
    }

    /**
     * Determine if the user has the given permission.
     *
     * @param string|Permission $permission
     *
     * @return bool
     */
    public function hasDirectPermission($permission)
    {
        if (is_string($permission)) {
            $permission = app(Permission::class)->findByName($permission);

            if (!$permission) {
                return false;
            }
        }

        return $this->permissions->contains('id', $permission->id);
    }

    /**
     * @param $role
     *
     * @return Role
     */
    protected function getStoredRole($role)
    {
        if (is_string($role)) {
            return app(Role::class)->findByName($role);
        }

        return $role;
    }

    /**
     * Return all  permissions the directory coupled to the user.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getDirectPermissions()
    {
        return $this->permissions;
    }

    /**
     * Return all the permissions the user has via roles.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPermissionsViaRoles()
    {
        return $this->load('roles', 'roles.permissions')
            ->roles->flatMap(function ($role) {
                return $role->permissions;
            })->sort()->values();
    }

    /**
     * Return all the permissions the user has, both directly and via roles.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissions()
    {
        return $this->permissions->merge($this->getPermissionsViaRoles())->sort()->values();
    }
}
