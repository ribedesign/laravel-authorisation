<?php

namespace Ribedesign\Authorisation;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Ribedesign\Authorisation\Contracts\Role as RoleContract;
use Ribedesign\Authorisation\Contracts\Permission as PermissionContract;
use Ribedesign\Authorisation\Contracts\Object as ObjectContract;
use Ribedesign\Authorisation\Contracts\Action as ActionContract;

class AuthorisationServiceProvider extends ServiceProvider
{
    /**
     * @param \Ribedesign\Authorisation\AuthorisationRegistrar $authorisationLoader
     */
    public function boot(AuthorisationRegistrar $authorisationLoader)
    {
        $this->publishes([
            __DIR__.'/../resources/config/laravel-authorisation.php' => $this->app->configPath().'/'.'laravel-authorisation.php',
        ], 'config');

        if (! class_exists('CreateAuthorisationTables')) {
            // Publish the migration
            $timestamp = date('Y_m_d_His', time());
            $this->publishes([
                __DIR__.'/../resources/migrations/create_authorisation_tables.php.stub' => $this->app->databasePath().'/migrations/'.$timestamp.'_create_authorisation_tables.php',
            ], 'migrations');
        }

        $this->registerModelBindings();

        $authorisationLoader->registerPermissions();
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../resources/config/laravel-authorisation.php',
            'laravel-authorisation'
        );

        $this->registerBladeExtensions();
    }

    protected function registerModelBindings()
    {
        $config = $this->app->config['laravel-authorisation.models'];

        $this->app->bind(PermissionContract::class, $config['permission']);
        $this->app->bind(RoleContract::class, $config['role']);
        $this->app->bind(ObjectContract::class, $config['object']);
        $this->app->bind(ActionContract::class, $config['action']);
    }

    protected function registerBladeExtensions()
    {
        $this->app->afterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {
            $bladeCompiler->directive('role', function ($role) {
                return "<?php if(auth()->check() && auth()->user()->hasRole({$role})): ?>";
            });
            $bladeCompiler->directive('endrole', function () {
                return '<?php endif; ?>';
            });

            $bladeCompiler->directive('hasrole', function ($role) {
                return "<?php if(auth()->check() && auth()->user()->hasRole({$role})): ?>";
            });
            $bladeCompiler->directive('endhasrole', function () {
                return '<?php endif; ?>';
            });

            $bladeCompiler->directive('hasanyrole', function ($roles) {
                return "<?php if(auth()->check() && auth()->user()->hasAnyRole({$roles})): ?>";
            });
            $bladeCompiler->directive('endhasanyrole', function () {
                return '<?php endif; ?>';
            });

            $bladeCompiler->directive('hasallroles', function ($roles) {
                return "<?php if(auth()->check() && auth()->user()->hasAllRoles({$roles})): ?>";
            });
            $bladeCompiler->directive('endhasallroles', function () {
                return '<?php endif; ?>';
            });
        });
    }
}
