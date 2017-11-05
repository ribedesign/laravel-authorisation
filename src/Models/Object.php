<?php

namespace Ribedesign\Authorisation\Models;

use Illuminate\Database\Eloquent\Model;
use Ribedesign\Authorisation\Traits\RefreshesObjectCache;
use Ribedesign\Authorisation\Exceptions\ObjectDoesNotExist;
use Ribedesign\Authorisation\Contracts\Object as ObjectContract;

class Object extends Model implements ObjectContract
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

        $this->setTable(config('laravel-authorisation.table_names.objects'));
    }

    /**
     * An object must be applied to permissions.
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
     * Find an object by its name.
     *
     * @param string $name
     *
     * @throws ObjectDoesNotExist
     *
     * @return Object
     */
    public static function findByName($name)
    {
        $object = static::where('name', $name)->first();

        if (!$object) {
            throw new ObjectDoesNotExist();
        }

        return $object;
    }
}
