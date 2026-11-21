<?php

namespace App\Providers;

use App\Contracts\ProductRepository;
use App\Contracts\Purchasing;
use App\Contracts\TransactionRepository;
use App\Contracts\UserRepository;
use App\Database\PdoConnection;
use App\Repositories\PdoProductRepository;
use App\Repositories\PdoTransactionRepository;
use App\Repositories\PdoUserRepository;
use App\Services\PurchaseService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(PdoConnection::class, fn (): PdoConnection => new PdoConnection(DB::connection()));
        $this->app->bind(ProductRepository::class, PdoProductRepository::class);
        $this->app->bind(UserRepository::class, PdoUserRepository::class);
        $this->app->bind(TransactionRepository::class, PdoTransactionRepository::class);
        $this->app->bind(Purchasing::class, PurchaseService::class);
    }

    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower($request->string('email', '', strict: true)->toString()).'|'.$request->ip()));
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        Paginator::defaultView('partials.pagination');
    }
}
