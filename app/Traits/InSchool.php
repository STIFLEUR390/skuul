<?php

namespace App\Traits;

use App\Models\School;
use App\Models\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait InSchool
{
    /**
     * Give a new record the school it is being created in, and keep every read
     * inside that school.
     *
     * A write never has to remember the school. Code that names a school
     * keeps it, so seeders, imports, and transfers stay in control.
     */
    public static function bootInSchool(): void
    {
        static::addGlobalScope(new SchoolScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('school_id') === null && current_school_id() !== null) {
                $model->setAttribute('school_id', current_school_id());
            }
        });
    }

    /**
     * Limit the query to records owned by one school.
     *
     * With no argument this uses the school of the current request, which the
     * global scope already does; naming a school is how a caller says it reads
     * another campus on purpose, so the global scope steps aside.
     *
     * @param  Builder  $query
     */
    public function scopeInSchool($query, School|int|null $school = null): Builder
    {
        if ($school !== null) {
            $query->withoutGlobalScope(SchoolScope::class);
        }

        $schoolId = $school instanceof School ? $school->id : ($school ?? current_school_id());

        return $query->where($this->getTable().'.school_id', $schoolId);
    }
}
