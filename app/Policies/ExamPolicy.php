<?php

namespace App\Policies;

use App\Models\AcademicPeriod;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExamPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        if ($user->can('read exam')) {
            return true;
        }
    }

    public function view(User $user, Exam $exam)
    {
        if ($user->can('read exam') && $exam->academicPeriod?->school_id == current_school_id()) {
            return true;
        }
    }

    public function create(User $user)
    {
        if ($user->can('create exam')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create an exam in a chosen period.
     */
    public function createForAcademicPeriod(User $user, AcademicPeriod $academicPeriod): bool
    {
        return $user->can('create exam')
            && $academicPeriod->school_id === current_school_id()
            && $academicPeriod->status->acceptsExamPlanning()
            && $academicPeriod->academicYear?->status->acceptsExamPlanning() === true;
    }

    /**
     * Determine whether an existing exam may be assigned to a period.
     */
    public function updateForAcademicPeriod(User $user, AcademicPeriod $academicPeriod): bool
    {
        return $user->can('update exam')
            && $academicPeriod->school_id === current_school_id()
            && $academicPeriod->status->acceptsExamPlanning()
            && $academicPeriod->academicYear?->status->acceptsExamPlanning() === true;
    }

    public function update(User $user, Exam $exam): bool
    {
        $academicPeriod = $exam->academicPeriod;

        return $academicPeriod !== null
            && $academicPeriod->school_id === current_school_id()
            && $user->can('update exam')
            && $academicPeriod->status->acceptsExamPlanning()
            && $academicPeriod->academicYear?->status->acceptsExamPlanning() === true;
    }

    public function delete(User $user, Exam $exam): bool
    {
        $academicPeriod = $exam->academicPeriod;

        return $academicPeriod !== null
            && $academicPeriod->school_id === current_school_id()
            && $user->can('delete exam')
            && $academicPeriod->isOpen()
            && $academicPeriod->academicYear?->isOpen() === true;
    }

    public function restore(User $user, Exam $exam)
    {
        //
    }

    public function forceDelete(User $user, Exam $exam)
    {
        //
    }
}
