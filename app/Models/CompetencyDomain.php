<?php

namespace App\Models;

use App\Traits\InSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A subject area a nursery or primary level assesses competencies in.
 *
 * A domain with no level belongs to the whole school; one with a level is
 * assessed by that level only.
 */
class CompetencyDomain extends Model
{
    use InSchool;

    protected $fillable = ['school_id', 'academic_level_id', 'name', 'name_en', 'position'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['position' => 0];

    /**
     * @var array<string, string>
     */
    protected $casts = ['position' => 'integer'];

    /** @return BelongsTo<AcademicLevel, $this> */
    public function academicLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class);
    }

    /** @return HasMany<Competency, $this> */
    public function competencies(): HasMany
    {
        return $this->hasMany(Competency::class)->orderBy('position');
    }

    /**
     * Keep the domains assessed by a level, and the school-wide ones.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForLevel(Builder $query, ?int $academicLevelId): Builder
    {
        return $query->where(fn (Builder $query) => $query->whereNull('academic_level_id')
            ->when($academicLevelId !== null, fn (Builder $query) => $query->orWhere('academic_level_id', $academicLevelId)));
    }
}
