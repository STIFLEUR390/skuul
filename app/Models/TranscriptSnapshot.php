<?php

namespace App\Models;

use App\Models\Scopes\SchoolScope;
use App\Traits\InSchool;
use Database\Factories\TranscriptSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class TranscriptSnapshot extends Model
{
    /** @use HasFactory<TranscriptSnapshotFactory> */
    use HasFactory;

    use InSchool;

    public const UPDATED_AT = null;

    protected $fillable = ['school_id', 'student_record_id', 'revision', 'payload', 'reason', 'issued_at', 'issued_by'];

    protected $casts = ['revision' => 'integer', 'payload' => 'array', 'issued_at' => 'datetime', 'created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('An issued transcript cannot be changed. Issue a new revision instead.');
        });
        static::deleting(function (): void {
            throw new RuntimeException('An issued transcript cannot be deleted.');
        });
    }

    /** @return BelongsTo<StudentRecord, $this> */
    public function studentRecord(): BelongsTo
    {
        // A transcript names the campus that issued it, and the learner may have
        // moved to another campus since, so the campus scope must not hide them.
        return $this->belongsTo(StudentRecord::class)->withoutGlobalScope(SchoolScope::class);
    }
}
