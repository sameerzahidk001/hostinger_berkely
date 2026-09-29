<?php

namespace App\Models;

use App\Traits\TracksAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseAgenda extends Model
{
    use HasFactory, TracksAudit;

    protected $fillable = [
        'course_id', 'subject', 'delivery_type', 'country_id', 'city',
        'from', 'to', 'inquiry', 'description', 'created_by', 'updated_by',
    ];

    // Add this relationship
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public static function deliveryTypeOptions(): array
    {
        return [
            'Virtual' => 'Live Online Instructor-Led',
            'In Person' => 'Face-to-Face Classroom',
        ];
    }

    public static function deliveryTypeAllLabel(): string
    {
        return 'Live Online Instructor-Led & Face-to-Face Classroom';
    }

    public static function deliveryTypeLabel(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === 'Virtual & Classroom') {
            return self::deliveryTypeAllLabel();
        }

        $options = self::deliveryTypeOptions();
        if (isset($options[$value])) {
            return $options[$value];
        }

        return $value;
    }

    public function getDeliveryTypeLabelAttribute(): string
    {
        return self::deliveryTypeLabel($this->delivery_type);
    }
}