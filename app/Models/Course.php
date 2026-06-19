<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TracksAudit;
class Course extends Model
{
    use HasFactory, SoftDeletes, TracksAudit;
    
    protected $fillable = [
        
        'title','short_name','slug','type','subject_id','description','short_description', 'awarded_by',
        'price','tax_percentage','currency','course_brochure','starting_date',
        'course_duration','duration_unit','duration_mode',
        'no_of_exams','exams_mode',
        'learning_methodology',
        'no_of_lectures','no_of_practice_mocks',
        'thumbnail','post_image','video',
        'free_application','is_free','docs_required',
        'with_subject','exam_information', 'exam_location', 'other_benifits', 'eligibility',
        'offered_by','benifits','vision_and_mission','who_can_do', 'exam_dates', 'exam_reg_deadline','exam_passing_criteria',
        'course_structure_overview_first','course_exam_format_duration_overview','course_structure_overview','exam_location_paragraph', 'salary', 'career_path', 
        'overview_img', 'overview_video_url', 'reg_iframe', 'image_alts',
        'performance_standard_heading', 'performance_standard_description', 'performance_standard_section',
        'overview_section', 'benefits_section', 'who_can_do_section', 'eligibility_section', 'learning_methodology_section',
        'career_path_section', 'exam_section', 'success_stories','success_stories_link_text','alumni_benefits_description', 'contact_us_section', 'contact_us_text',
        'custom_section_01', 'custom_section_01_description' ,'exam_info_custom_01', 'lecture_plan_section',
        'custom_videos_section', 'custom_videos_desc','custom_videos','related_courses_section','faq_section','banner_section'

    ];

    protected $casts = [
        'instructor_id' => 'array',
        'starting_date' => 'datetime',
        'learning_methodology' => 'array',
        'offered_by' => 'array',
        'benifits' => 'array',
        'who_can_do' => 'array',
        'exam_location' => 'array',
        'custom_videos ' => 'array',
        'image_alts' => 'array',
    ];
    
    public function courseObjectivePoints()
    {
        return $this->hasMany(CourseObjective::class, 'course_id', 'id');
    }

    // A course has many key courseBeneficiaries
    public function courseBeneficiaries()
    {
        return $this->hasMany(CourseBeneficiaries::class, 'course_id', 'id');
    }

    // A course has many key courseRewards
    public function courseRewards()
    {
        return $this->hasMany(CourseReward::class, 'course_id', 'id');
    }
    // A course has many key CourseSyllabus
    public function courseSyllabus()
    {
        return $this->hasMany(CourseSyllabus::class, 'course_id', 'id');
    }
    // A course has many key CourseEnrollment
    public function courseEnrollments()
    {
        return $this->hasMany(CourseEnrollment::class, 'course_id', 'id');
    }
    // A course has many key CourseFaq
    public function courseFaq()
    {
        return $this->hasMany(CourseFaq::class, 'course_id', 'id');
    }

    public function courseCategory()
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'id');
    }
    
    public function subjects()
    {
        return $this->belongsToMany(Subject::class);
    }
    public function dynamicLabel()
    {
        return $this->hasOne(DynamicLabels::class, 'course_id', 'id');
    }
    public function seo()
    {
        return $this->hasOne(PagesSEO::class, 'course_id');
    }
   

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_course');
    }

    public function schools()
    {
        return $this->belongsToMany(School::class, 'school_courses');
    }

    // Define the related courses (linked courses)
    public function relatedCourses()
    {
        return $this->belongsToMany(Course::class, 'related_courses', 'course_id', 'linked_course_id');
    }

    // Define the inverse relation for the courses that this course is linked to
    public function linkedByCourses()
    {
        return $this->belongsToMany(Course::class, 'related_courses', 'linked_course_id', 'course_id');
    }
    public function testimonials()
    {
        return $this->hasMany(CourseTestimonial::class);
    }
    // public function courseSubCategory()
    // {
    //     return $this->belongsTo(SubCategory::class, 'subcategory_id', 'id');
    // }

    //Get the child/related courses for the bundle.
    // public function bundelChildCourses()
    // {
    //     return $this->belongsToMany(self::class, 'course_bundels', 'bundel_id', 'course_id');
    // }

    
    //Get the bundles that the course belongs to.
    // public function courseParentBundles()
    // {
    //     return $this->belongsToMany(self::class, 'course_bundels', 'course_id', 'bundel_id');
    // }

    // realtionship for new structure
    public function courseStructures()
    {
        return $this->hasMany(CourseStructure::class, 'courses_id', 'id');
    }

    public function courseFeePackages()
    {
        return $this->hasMany(CourseFee::class, 'courses_id', 'id');
    }

    public function courseStructuresFirst()
    {
        return $this->hasMany(CourseStructureFirst::class, 'courses_id', 'id');
    }
    // old fee structure
    public function feePackages()
    {
        return $this->hasMany(CourseFeePackages::class, 'courses_id', 'id');
    }

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($course) {
    //         $course->slug = Str::slug($course->title);
    //     });

    //     static::updating(function ($course) {
    //         $course->slug = Str::slug($course->title);
    //     });
    // }
}