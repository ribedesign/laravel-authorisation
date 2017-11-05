<?php

namespace Ribedesign\Authorisation\Traits;

use Ribedesign\Authorisation\AuthorisationRegistrar;

trait RefreshesAuthorisationCache
{
    public static function bootRefreshesAuthorisationCache()
    {
        static::updated(function () {
            app(AuthorisationRegistrar::class)->forgetCachedPermissions();
        });

        static::deleted(function () {
            app(AuthorisationRegistrar::class)->forgetCachedPermissions();
        });
    }

    /**
     *  Forget the cached permissions.
     */
    public function forgetCachedPermissions()
    {
        app(AuthorisationRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Get the current cached permissions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected static function getPermissions()
    {
        return app(AuthorisationRegistrar::class)->getPermissions();
    }
}
