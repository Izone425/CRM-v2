<?php
// filepath: /var/www/html/timeteccrm/app/Models/TrainingSession.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TrainingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'trainer_profile',
        'year',
        'training_category',
        'training_module',
        'session_number',
        'day1_date', 'day1_start_time', 'day1_end_time', 'day1_module', 'day1_deck_link', 'day1_meeting_link', 'day1_meeting_id', 'day1_meeting_password',
        'day2_date', 'day2_start_time', 'day2_end_time', 'day2_module', 'day2_deck_link', 'day2_meeting_link', 'day2_meeting_id', 'day2_meeting_password',
        'day3_date', 'day3_start_time', 'day3_end_time', 'day3_module', 'day3_deck_link', 'day3_meeting_link', 'day3_meeting_id', 'day3_meeting_password',
        'max_participants',
        'status',
        'is_manual_schedule'
    ];

    protected $casts = [
        'day1_date' => 'date',
        'day2_date' => 'date',
        'day3_date' => 'date',
        'day1_start_time' => 'datetime',
        'day1_end_time' => 'datetime',
        'day2_start_time' => 'datetime',
        'day2_end_time' => 'datetime',
        'day3_start_time' => 'datetime',
        'day3_end_time' => 'datetime',
        'is_manual_schedule' => 'boolean'
    ];

    // Enum constants
    public const TRAINER_PROFILES = [
        'TRAINER_1' => 'Trainer 1',
        'TRAINER_2' => 'Trainer 2',
        'TRAINER_3' => 'Trainer 3'
    ];

    public const TRAINING_CATEGORIES = [
        'HRDF' => 'HRDF Training',
        'WEBINAR' => 'Webinar Training'
    ];

    public const TRAINING_MODULES = [
        'OPERATIONAL' => 'Operational Module',
        'STRATEGIC' => 'Strategic Module'
    ];

    public const SESSION_STATUS = [
        'DRAFT' => 'Draft',
        'SCHEDULED' => 'Scheduled',
        'COMPLETED' => 'Completed',
        'CANCELLED' => 'Cancelled'
    ];

    // Accessors
    public function getTrainerNameAttribute(): string
    {
        return self::TRAINER_PROFILES[$this->trainer_profile] ?? $this->trainer_profile;
    }

    public function getCategoryNameAttribute(): string
    {
        return self::TRAINING_CATEGORIES[$this->training_category] ?? $this->training_category;
    }

    public function getModuleNameAttribute(): string
    {
        return self::TRAINING_MODULES[$this->training_module] ?? $this->training_module;
    }

    public function getMaxParticipantsAttribute($value): int
    {
        // Auto-set based on category if not manually set
        if ($value === null) {
            return $this->training_category === 'HRDF' ? 50 : 100;
        }
        return $value;
    }

    public function getStartDateAttribute(): Carbon
    {
        return Carbon::parse($this->day1_date);
    }

    public function getEndDateAttribute(): Carbon
    {
        return Carbon::parse($this->day3_date);
    }

    public function getTrainingDatesAttribute(): array
    {
        return [
            $this->day1_date->format('Y-m-d'),
            $this->day2_date->format('Y-m-d'),
            $this->day3_date->format('Y-m-d')
        ];
    }

    public function getStatusColorAttribute(): string
    {
        $now = Carbon::now();
        $startDate = $this->getStartDateAttribute();

        if ($startDate->isPast()) {
            return 'bg-gray-300 text-gray-600'; // Light grey for past dates
        }

        switch ($this->status) {
            case 'SCHEDULED':
                return 'bg-red-500 text-white'; // Red for completed training setting
            case 'DRAFT':
                return 'bg-green-500 text-white'; // Green for available for training setting
            default:
                return 'bg-gray-500 text-white'; // Dark grey for weekends/unavailable
        }
    }

    public function isDayWeekend(int $day): bool
    {
        $date = $this->{"day{$day}_date"};
        return $date->isWeekend();
    }

    // Scopes
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForTrainer($query, string $trainer)
    {
        return $query->where('trainer_profile', $trainer);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('training_category', $category);
    }
}
