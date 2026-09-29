<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Meeting extends Model
{
    protected $table = 'meetings';

    protected $primaryKey = 'meeting_id';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'agenda',
        'meeting_date',
        'meeting_time',
        'location_or_link',
        'dyte_meeting_id',
        'created_by',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'meeting_time' => 'string',
        'scheduled_at' => 'datetime',
    ];

    // Scheduled meetings stop accepting participation after their calendar day.
    public function getStatusAttribute(?string $value): ?string
    {
        if ($value === 'scheduled' && $this->scheduled_at?->lt(Carbon::today(config('app.timezone')))) {
            return 'completed';
        }

        return $value;
    }

    // Combines meeting_date and meeting_time into one Carbon datetime.
    public function getScheduledAtAttribute(): ?Carbon
    {
        $date = $this->getRawOriginal('meeting_date') ?: $this->meeting_date;
        $time = $this->getRawOriginal('meeting_time') ?: $this->meeting_time;

        if (! $date) {
            return null;
        }

        // Keep only the date part so stored datetimes do not create double-time parse errors.
        $date = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        if (! $time) {
            return Carbon::parse($date);
        }

        // Keep only the time part if older records accidentally stored a full datetime.
        $time = $time instanceof Carbon ? $time->format('H:i:s') : (string) $time;
        $time = preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $time)
            ? $time
            : Carbon::parse($time)->format('H:i:s');

        return Carbon::parse("{$date} {$time}");
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    // Finds completed or already elapsed meetings for past-meeting lists.
    public function scopePast(Builder $query): Builder
    {
        return $query->where(function (Builder $past) {
            $past->where('status', '!=', 'scheduled')
                ->orWhereDate('meeting_date', '<', Carbon::today(config('app.timezone'))->toDateString());
        });
    }
}
