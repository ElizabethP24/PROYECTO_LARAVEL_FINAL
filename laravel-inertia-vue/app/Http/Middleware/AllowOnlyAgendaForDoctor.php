<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Doctor;

class AllowOnlyAgendaForDoctor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Only act for authenticated users with role 'doctor'
        if ($user && $user->role === 'doctor') {
            $route = $request->route();
            $routeName = $route ? $route->getName() : null;

            // Routes (or patterns) the doctor is allowed to access
            $allowed = [
                'doctors.agenda',
                'doctors.publicProfile', // allow public profile if needed
                'logout', // allow logout
                'profile.show', // profile
                'appointments.*', // allow appointments routes (index, show, approve, etc.)
            ];

            $allowedMatch = false;
            if ($routeName) {
                foreach ($allowed as $pattern) {
                    if (Str::endsWith($pattern, '*')) {
                        $base = rtrim($pattern, '*');
                        if (Str::startsWith($routeName, $base)) {
                            $allowedMatch = true;
                            break;
                        }
                    } else {
                        if ($routeName === $pattern) {
                            $allowedMatch = true;
                            break;
                        }
                    }
                }
            }

            // Also allow logout path (POST /logout) even if route has no name
            if (!$allowedMatch && $request->isMethod('POST') && $request->path() === 'logout') {
                $allowedMatch = true;
            }

            if (!$allowedMatch) {
                // Find the doctor's slug to redirect to their agenda
                $doctor = Doctor::where('user_id', $user->id)->first();
                $param = $doctor ? $doctor->slug : $user->id;

                return redirect()->route('doctors.agenda', $param);
            }
        }

        return $next($request);
    }
}
