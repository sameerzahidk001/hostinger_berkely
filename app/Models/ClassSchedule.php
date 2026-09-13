<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClassSchedule extends Model
{
    protected $fillable = [
        'batch_name',
        'course_id',
        'instructor_id',
        'scheduled_at',
        'duration_minutes',
        'zoho_link',
        'zoho_calendar_event_uid',
        'title',
        'notes',
        'status',
        'created_by_admin_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_schedule_student', 'class_schedule_id', 'student_id')
            ->withTimestamps();
    }

    public function durationMinutes(): int
    {
        return max(15, (int) ($this->duration_minutes ?: 60));
    }

    public function endsAt()
    {
        return $this->scheduled_at
            ? $this->scheduled_at->copy()->addMinutes($this->durationMinutes())
            : null;
    }

    public function calendarTitle(): string
    {
        return $this->title ?: $this->batch_name;
    }

    public function toFullCalendarEvent(string $url): array
    {
        return [
            'id' => $this->id,
            'title' => $this->calendarTitle(),
            'start' => optional($this->scheduled_at)->toIso8601String(),
            'end' => optional($this->endsAt())->toIso8601String(),
            'url' => $url,
            'color' => $this->status === 'cancelled' ? '#ed5565' : ($this->status === 'completed' ? '#1ab394' : '#1c84c6'),
        ];
    }

    public function toIcsEvent(string $joinUrl = ''): string
    {
        $tz = config('app.timezone', 'Asia/Dubai');
        $start = $this->scheduled_at?->copy()->timezone($tz);
        $end = $this->endsAt()?->copy()->timezone($tz);
        $summary = $this->escapeIcs($this->calendarTitle());
        $description = $this->escapeIcs(trim(
            ($this->course->title ?? '') . "\n" .
            ($this->instructor->name ? 'Instructor: ' . $this->instructor->name : '') . "\n" .
            ($this->notes ?: '') . "\n" .
            ($this->zoho_link ? 'Join: ' . $this->zoho_link : '')
        ));
        $uid = 'class-schedule-' . $this->id . '@' . parse_url(config('app.url'), PHP_URL_HOST);

        $lines = [
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . now($tz)->format('Ymd\THis'),
            'DTSTART;TZID=' . $tz . ':' . ($start ? $start->format('Ymd\THis') : ''),
            'DTEND;TZID=' . $tz . ':' . ($end ? $end->format('Ymd\THis') : ''),
            'SUMMARY:' . $summary,
            'DESCRIPTION:' . $description,
        ];
        if ($joinUrl || $this->zoho_link) {
            $lines[] = 'URL:' . ($joinUrl ?: $this->zoho_link);
        }
        $lines[] = 'END:VEVENT';

        return implode("\r\n", $lines);
    }

    protected function escapeIcs(string $value): string
    {
        return str_replace(["\\", ";", ",", "\n", "\r"], ["\\\\", "\\;", "\\,", "\\n", ''], $value);
    }
}
