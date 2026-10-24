<?php

namespace App\Models;

use App\Traits\InSchool;
use Brick\Money\Money as BrickMoney;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A cashier's day at the counter.
 *
 * Cash payments are attached to the session that was open when the counter
 * took them, so the drawer can be counted against what the counter recorded.
 */
class CashSession extends Model
{
    use HasFactory;
    use InSchool;

    protected $fillable = [
        'school_id', 'opened_by', 'opened_at', 'opening_float',
        'closed_by', 'closed_at', 'counted_amount', 'note',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_float' => 'float',
        'counted_amount' => 'float',
    ];

    /** @return HasMany<StudentPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(StudentPayment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /** @return BelongsTo<User, $this> */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Limit the query to the sessions still at the counter.
     *
     * @param  Builder<CashSession>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('closed_at');
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    /**
     * What the till should hold: the float it started with plus the cash it took.
     */
    public function expected(): float
    {
        $minor = (int) $this->payments()->sum('amount');

        return round($this->opening_float
            + BrickMoney::ofMinor($minor, config('app.currency'))->getAmount()->toFloat(), 2);
    }

    /**
     * Counted minus expected: positive is a surplus, negative a shortage.
     */
    public function variance(): ?float
    {
        return $this->counted_amount === null ? null : round($this->counted_amount - $this->expected(), 2);
    }
}
