<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateUserLanguageController extends Controller
{
    /**
     * Store the language this person reads the interface in.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(config('app.supported_locales', [])))],
        ]);

        $request->user()->forceFill(['locale' => $validated['locale']])->save();

        return back()->with('success', __('Language updated.'));
    }
}
