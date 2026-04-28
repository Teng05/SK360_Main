<?php

namespace App\Models;

use App\Services\DyteService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class Event extends Model
{
    protected $table = 'events';
    protected $primaryKey = 'event_id';
    public $timestamps = false;

    protected $fillable = [
        'created_by',
        'title',
        'description',
        'location',
        'event_type',
        'start_datetime',
        'end_datetime',
        'visibility',
        'dyte_meeting_id',
        'dyte_room_name',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function scopeMeetings(Builder $query): Builder
    {
        return $query->where('event_type', 'meeting');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_datetime', '>', now());
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('start_datetime', '<=', now());
    }

    public function isMeeting(): bool
    {
        return $this->event_type === 'meeting';
    }

    public function hasDyteRoom(): bool
    {
        return !empty($this->dyte_meeting_id);
    }

    public function startsAt(): ?CarbonInterface
    {
        return $this->start_datetime;
    }

    public function syncDyteRoom(DyteService $dyte): self
    {
        if ($this->hasDyteRoom()) {
            return $this;
        }

        $dyteMeeting = $dyte->createMeeting($this->title ?: 'SK Meeting');

        if (empty($dyteMeeting['id'])) {
            throw new RuntimeException('Dyte meeting could not be created for this event.');
        }

        $this->dyte_meeting_id = $dyteMeeting['id'];
        $this->dyte_room_name = $dyteMeeting['roomName']
            ?? $dyteMeeting['room_name']
            ?? $dyteMeeting['id'];
        $this->visibility = $this->visibility ?: 'officials_only';

        if (!$this->end_datetime && $this->start_datetime) {
            $this->end_datetime = $this->start_datetime->copy()->addHour();
        }

        $this->save();
        $this->refresh();

        return $this;
    }
}
