<?php

namespace App\Http\Controllers;

use App\Models\Scopes\SchoolScope;
use App\Models\StudentRecord;
use Illuminate\View\View;

/**
 * One student's money: what they owe, what they paid, what is held for them.
 *
 * The office needs one page that answers a parent at the counter, rather than
 * a list of invoices that each answer a piece of the question.
 */
class StudentAccountController extends Controller
{
    /**
     * Show everything the school knows about one student's account.
     */
    public function show(string $studentRecord): View
    {
        // A learner who moved campus keeps an account here, so the record is
        // found by id and the account itself decides whether it belongs here.
        $studentRecord = StudentRecord::query()
            ->withoutGlobalScope(SchoolScope::class)
            ->findOrFail($studentRecord);

        abort_unless(
            auth()->user()?->can('read fee invoice') === true
                && !auth()->user()->isPortalOnly()
                && $studentRecord->hasAccountInSchool(),
            403,
        );

        $studentRecord->loadMissing('user');

        return view('pages.fee.account.show', ['enrollment' => $studentRecord]);
    }
}
