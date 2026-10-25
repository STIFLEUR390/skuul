<?php

namespace App\Models;

use App\Enums\CompetencyLevel;
use App\Traits\InSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The mastery level one learner reached on one competency in one period.
 */
class CompetencyAssessment extends Model
{
    use InSchool;

    protected $fillable = ['school_id', 'student_record_id', 'competency_id', 'academic_period_id', 'level', 'comment', 'assessed_by'];

    /**
     * @var array<string, string>
     */
    protected $casts = ['level' => CompetencyLevel::class];

    /** @return BelongsTo<StudentRecord, $this> */
    public function studentRecord(): BelongsTo
    {
        return $this->belongsTo(StudentRecord::class);
    }

    /** @return BelongsTo<Competency, $this> */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    /** @return BelongsTo<AcademicPeriod, $this> */
    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
