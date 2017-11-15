<?php

namespace Ribedesign\Authorisation\Models;

use Illuminate\Database\Eloquent\Model;
use Ribedesign\Authorisation\Traits\RefreshesActionCache;
use Ribedesign\Authorisation\Exceptions\ActionDoesNotExist;
use Ribedesign\Authorisation\Contracts\Action as ActionContract;

class Action extends Model implements ActionContract
{
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

        $this->setTable(config('laravel-authorisation.table_names.actions'));
    }

    /**
     * An action must be applied to permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(
            config('laravel-authorisation.models.permission')
        );
    }

    /**
     * Find an action by its name.
     *
     * @param string $name
     *
     * @throws ActionDoesNotExist
     *
     * @return Action
     */
    public static function findByName($name)
    {
        $action = static::where('name', $name)->first();

        if (!$action) {
            throw new ActionDoesNotExist();
        }

        return $action;
    }
}
