<?php

namespace Ribedesign\Authorisation\Models;

use Illuminate\Database\Eloquent\Model;
use Ribedesign\Authorisation\Traits\RefreshesAuthorisationCache;
use Ribedesign\Authorisation\Exceptions\PermissionDoesNotExist;
use Ribedesign\Authorisation\Contracts\Permission as PermissionContract;

class Permission extends Model implements PermissionContract
{
    use RefreshesAuthorisationCache;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    public $guarded = ['id'];

    /**
     * @var array
     */
    protected $defaults = array(
        'object_id' => 0,
        'action_id' => 0,
    );

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setRawAttributes($this->defaults, true);
        parent::__construct($attributes);

        $this->setTable(config('laravel-authorisation.table_names.permissions'));
    }

    /**
     * A permission can be applied to roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(
            config('laravel-authorisation.models.role'),
            config('laravel-authorisation.table_names.role_has_permissions')
        );
    }

    /**
     * A permission can be applied to users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(
            config('auth.model') ?: config('auth.providers.users.model'),
            config('laravel-authorisation.table_names.user_has_permissions')
        );
    }

    /**
     * Find a permission by its name.
     *
     * @param string $name
     *
     * @throws PermissionDoesNotExist
     *
     * @return Permission
     */
    public static function findByName($name)
    {
        $permission = static::getPermissions()->where('name', $name)->first();

        if (!$permission) {
            throw new PermissionDoesNotExist();
        }

        return $permission;
    }
}
