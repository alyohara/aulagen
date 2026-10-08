<?php

namespace App\Providers;

use App\AI\AIManager;
use App\Support\AiSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AIManager::class, fn () => new AIManager());
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        AiSettings::apply();

        Gate::define('manage-courses', fn ($user) => $user->isTeacher());
        Gate::define('admin-panel', fn ($user) => $user->isAdmin());
        Gate::define('preview-course', fn ($user, $course) => $user->teaches($course));

        RateLimiter::for('ai', function (Request $request) {
            $limit = (int) config('ai.rate_limit', 10);

            return [
                Limit::perMinute($limit)->by($request->user()?->id ?: $request->ip()),
                Limit::perMinute($limit * 6)->by('ai-burst-'.$request->ip()),
            ];
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(8)->by($request->ip());
        });
    }
}
