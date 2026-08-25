<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\User;
use App\Services\System\SetupService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSetupAccessible
{
    /**
     * Handle an incoming request.
     *
     * Blocks access to setup routes once setup is complete.
     *
     * Primary gate: an immutable setup-completed marker persisted by
     * SetupService::markSetupComplete(). While the marker exists, every
     * /setup/* route is denied in ALL environments - including the anonymous
     * POST steps that can create admin users or run migrations - except the
     * admin-authenticated reset endpoint which clears the marker itself.
     * This closes the takeover path where a production install could be
     * re-opened by emptying one table, and the non-production path where the
     * wizard was always reachable.
     *
     * Fallback: installs predating the marker keep the historical data-derived
     * heuristic in production only (User, Currency, ExchangeRate and Branch
     * records all exist).
     */
    public function __construct(
        protected SetupService $setupService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->setupService->isCompleted()) {
            // While the marker exists the ONLY reachable setup route is the
            // admin-authenticated reset (which clears the marker itself);
            // every other setup route is denied in ALL environments. Return
            // directly for the reset carve-out - do NOT fall through to the
            // data-derived fallback, which would re-block setup.reset on any
            // completed production install.
            if ($request->routeIs('setup.reset')) {
                return $next($request);
            }

            abort(403);
        }

        if (! app()->isProduction()) {
            return $next($request);
        }

        // Data-derived fallback for installs without the marker row yet.
        $setupComplete = User::exists()
            && Currency::exists()
            && ExchangeRate::exists()
            && Branch::exists();

        if ($setupComplete) {
            abort(403);
        }

        return $next($request);
    }
}
