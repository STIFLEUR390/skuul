<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;

/**
 * Keep every read inside the campus the request works in.
 *
 * Registering this once, on the trait, replaces the hundreds of places that
 * could forget to narrow their query. Console commands, jobs and the installer
 * run with no campus context, so the scope does nothing for them: a read is
 * only narrowed when there is a campus to narrow it to.
 *
 * Reading another campus on purpose goes through inSchool($school) or an
 * explicit withoutGlobalScope(SchoolScope::class). A query that already names
 * its school also keeps its own answer, which is what cross-campus work does.
 */
class SchoolScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $schoolId = current_school_id();

        if ($schoolId !== null && !$this->namesItsSchool($builder)) {
            $builder->where($model->getTable().'.school_id', $schoolId);
        }
    }

    /**
     * Whether the query already says which campus it reads.
     *
     * Carrying money, placing a learner and moving them between campuses name
     * the school on either side of the move; narrowing those queries to the
     * current campus would only answer with nothing.
     */
    private function namesItsSchool(Builder $builder): bool
    {
        return $this->wheresNameTheirSchool($builder->getQuery()->wheres);
    }

    /**
     * Whether any of these conditions, groups included, names the campus.
     *
     * A scope such as studiedInSchool() wraps both campuses it accepts in one
     * group, so the answer is only found by looking inside the groups.
     *
     * @param  array<int, array<string, mixed>>  $wheres
     */
    private function wheresNameTheirSchool(array $wheres): bool
    {
        foreach ($wheres as $where) {
            $nested = $where['query'] ?? null;
            $nested = $nested instanceof Builder ? $nested->getQuery() : $nested;

            if ($nested instanceof QueryBuilder && $this->wheresNameTheirSchool($nested->wheres)) {
                return true;
            }

            $column = $where['column'] ?? null;

            if (!is_string($column) || Str::afterLast($column, '.') !== 'school_id') {
                continue;
            }

            if (($where['type'] ?? null) === 'In' || ($where['operator'] ?? null) === '=') {
                return true;
            }
        }

        return false;
    }
}
