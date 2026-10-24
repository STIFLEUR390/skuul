<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use Illuminate\View\View;

class CashSessionController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('read cash session') === true, 403);

        return view('pages.fee.cash-sessions.index', [
            'sessions' => CashSession::query()
                ->inSchool()
                ->with('openedBy')
                ->latest('opened_at')
                ->latest('id')
                ->paginate(15),
        ]);
    }
}
