<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    public const DRAFT = 'draft';
    public const SUBMITTED = 'submitted';
    public const APPROVED = 'approved';
    public const RETURNED = 'returned';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'observations' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ReportTask::class)->orderBy('position');
    }

    public function viewership(): HasMany
    {
        return $this->hasMany(ReportViewership::class)->orderBy('position');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(ReportAttendee::class)->orderBy('position');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(ReportIncident::class)->orderBy('position');
    }

    public function isEditableByCoordinator(): bool
    {
        return in_array($this->status, [self::DRAFT, self::RETURNED], true);
    }

    /** Minutes between start and end (handles services running past midnight). */
    public function durationMinutes(): ?int
    {
        if (! $this->start_time || ! $this->end_time) {
            return null;
        }
        [$sh, $sm] = array_map('intval', explode(':', $this->start_time));
        [$eh, $em] = array_map('intval', explode(':', $this->end_time));
        $diff = ($eh * 60 + $em) - ($sh * 60 + $sm);

        return $diff < 0 ? $diff + 1440 : $diff;
    }

    public function durationLabel(): string
    {
        $m = $this->durationMinutes();
        if ($m === null) {
            return '';
        }
        $h = intdiv($m, 60);
        $r = $m % 60;

        return trim(($h ? $h.' hr'.($h > 1 ? 's' : '') : '').' '.($r ? $r.' min'.($r > 1 ? 's' : '') : ''));
    }

    public function totalViewers(): int
    {
        return (int) $this->viewership->sum('viewers');
    }
}
