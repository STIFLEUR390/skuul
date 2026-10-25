<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One skill a learner is assessed on, inside a domain.
 */
class Competency extends Model
{
    protected $fillable = ['competency_domain_id', 'name', 'name_en', 'position'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['position' => 0];

    /**
     * @var array<string, string>
     */
    protected $casts = ['position' => 'integer'];

    /** @return BelongsTo<CompetencyDomain, $this> */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(CompetencyDomain::class, 'competency_domain_id');
    }
}
