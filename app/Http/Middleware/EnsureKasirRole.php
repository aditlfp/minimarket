<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKasirRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('filament.kasir.auth.login');
        }

        // Admin/manajer dialihkan ke panel admin
        if ($user->hasRole('admin') || $user->hasRole('manajer')) {
            return redirect('/admin');
        }

        // Staff gudang dialihkan ke panel admin
        if ($user->hasRole('staff_gudang')) {
            return redirect('/admin');
        }

        // Pastikan user kasir punya outlet
        if (! $user->outlet_id) {
            return redirect('/admin')->with('error', 'Akun kasir harus memiliki outlet.');
        }

        return $next($request);
    }
}
