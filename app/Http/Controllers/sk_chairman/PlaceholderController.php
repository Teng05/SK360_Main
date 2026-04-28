<?php

namespace App\Http\Controllers\sk_chairman;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PlaceholderController extends Controller
{
    public function show(string $title, string $message): View
    {
        abort_unless(auth()->check() && auth()->user()->role === 'sk_chairman', 403);

        return view('sk_chairman.placeholder', [
            'title' => $title,
            'message' => $message,
        ]);
    }
}
