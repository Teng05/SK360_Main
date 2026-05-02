<?php

// File guide: Handles route logic and page data for app/Http/Controllers/sk_pres/PlaceholderController.php.

namespace App\Http\Controllers\sk_pres;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PlaceholderController extends Controller
{
    public function show(string $title, string $message): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_president', 403);

        return view('sk_pres.placeholder', [
            'title' => $title,
            'message' => $message,
        ]);
    }
}
