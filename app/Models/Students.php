<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/* CREATE TABLE test.students (
    id                   int  NOT NULL  ,
    first_name           varchar(50)  NOT NULL  ,
    last_name            varchar(50)  NOT NULL  ,
    middle_name          varchar(20)  NOT NULL  ,
    gender               varchar(10)  NOT NULL  ,
    birth_date           date  NOT NULL  ,
    age                  int  NOT NULL  ,
    address              varchar(255)  NOT NULL  ,
    contacts             text  NOT NULL  ,
    course_id            int  NOT NULL  ,
    academic_year        int  NOT NULL  ,
    email                varchar(255)  NOT NULL  ,
    remarks              text    ,
    created_at           timestamp  NOT NULL DEFAULT current_timestamp() ,
    updated_at           timestamp  NOT NULL DEFAULT current_timestamp() ,
    profile_url          varchar(255)  NOT NULL  ,
    student_contact_id   int    ,
    student_parent_info  int    ,
    student_education_id int    ,
    student_personal_id  int    ,
    document_location_id int    ,
    CONSTRAINT pk_students PRIMARY KEY ( id )
 );

CREATE INDEX fk_students_courses ON test.students ( course_id );

CREATE INDEX idx_students_student_parent_info ON test.students ( student_parent_info );

CREATE INDEX idx_students_student_education_id ON test.students ( student_education_id );

CREATE INDEX idx_students_student_personal_id ON test.students ( student_personal_id );

CREATE INDEX idx_students_document_location_id ON test.students ( document_location_id );

CREATE INDEX fk_students_student_contacts ON test.students ( student_contact_id );

ALTER TABLE test.students ADD CONSTRAINT fk_students_courses FOREIGN KEY ( course_id ) REFERENCES test.courses( id ) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE test.students ADD CONSTRAINT fk_students_student_contacts FOREIGN KEY ( student_contact_id ) REFERENCES test.student_contacts( id ) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE test.students ADD CONSTRAINT fk_students_student_parents_info FOREIGN KEY ( student_parent_info ) REFERENCES test.student_parents_info( id ) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE test.students ADD CONSTRAINT fk_students_student_education_info FOREIGN KEY ( student_education_id ) REFERENCES test.student_education_info( id ) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE test.students ADD CONSTRAINT fk_students_students_personal_info FOREIGN KEY ( student_personal_id ) REFERENCES test.students_personal_info( id ) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE test.students ADD CONSTRAINT fk_students_document_locations FOREIGN KEY ( document_location_id ) REFERENCES test.document_locations( id ) ON DELETE NO ACTION ON UPDATE NO ACTION;

 */

final class Students extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $timestamps = true;

    protected $table = 'students';

    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'middle_name',
        'gender',
        'birth_date',
        'age',
        'address',
        'contacts',
        'course_id',
        'academic_year',
        'email',
        'remarks',
        'created_at',
        'updated_at',
        'profile_url',
        'student_contact_id',
        'student_parent_info',
        'student_education_id',
        'student_personal_id',
        'document_location_id',
        'student_id',
        'status',
        'clearance_status',

    ];

    // protected $primaryKey = 'id';

    private bool $autoIncrement = false;

    public function DocumentLocation()
    {
        return $this->belongsTo(document_locations::class, 'document_location_id');
    }

    public function Accounts()
    {
        return $this->hasOne(User::class, 'person_id', 'id');
    }

    public function Course()
    {
        return $this->belongsTo(Courses::class, 'course_id');
    }

    public function subjects()
    {
        return $this->hasManyThrough(
            Subject::class,
            Courses::class,
            'id', // Foreign key on Course table
            'course_id', // Foreign key on Subject table
            'course_id', // Local key on Students table
            'id' // Local key on Course table
        );
    }

    public function personalInfo()
    {
        return $this->hasOne(StudentPersonal::class, 'id', 'student_personal_id');
    }

    public function studentEducationInfo()
    {
        return $this->hasOne(StudentEducationInfo::class, 'id', 'student_education_id');
    }

    public function studentContactsInfo()
    {
        return $this->hasOne(StudentContact::class, 'id', 'student_contact_id');
    }

    public function studentParentInfo()
    {
        return $this->hasOne(StudentParentInfo::class, 'id', 'student_parent_info');
    }

    public function classEnrollments()
    {
        return $this->hasMany(class_enrollments::class, 'student_id', 'id');
    }

    public function subjectEnrolled()
    {
        return $this->hasMany(SubjectEnrolled::class, 'student_id', 'id');
    }

    public function Classes()
    {
        return $this->hasMany(class_enrollments::class, 'student_id', 'id');
    }

    // app/Models/Students.php

    public function enrollInClasses(): void
    {
        $subjectEnrollments = $this->subjectEnrolled;

        foreach ($subjectEnrollments as $subjectEnrollment) {
            $subject = $subjectEnrollment->subject;

            Log::info("Enrolling student {$this->id} in classes for subject: {$subject->code}");

            $classes = Classes::query()->where('subject_code', $subject->code)
                ->whereJsonContains('course_codes', "$this->course_id")

                ->where('academic_year', $subjectEnrollment->academic_year)
                ->where('semester', $subjectEnrollment->semester)
                ->get();

            Log::info('Found '.$classes->count()." classes for subject {$subject->code}");

            foreach ($classes as $class) {
                // Check if the student is already enrolled in the class
                $existingEnrollment = class_enrollments::query()->where('class_id', $class->id)
                    ->where('student_id', $this->id)
                    ->first();

                if (! $existingEnrollment) {
                    Log::info("Enrolling student {$this->id} in class {$class->id}");

                    class_enrollments::query()->create([
                        'class_id' => $class->id,
                        'student_id' => $this->id,
                    ]);
                } else {
                    Log::info("Student {$this->id} is already enrolled in class {$class->id}");
                }
            }
        }

        Log::info("Finished enrolling student {$this->id} in classes");
    }

    public function subjectsByYear($academicYear)
    {
        return $this->subjects()
            ->where('academic_year', $academicYear)
            ->get()
            ->map(fn ($subject): string => "{$subject->title} (Code: {$subject->code}, Units: {$subject->units})")->join(', ');
    }

    public function StudentTuition()
    {
        return $this->hasMany(StudentTuition::class, 'student_id', 'id');
    }

    /**
     * Get the attendance records for this student
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id', 'id');
    }

    public function StudentTransactions()
    {
        return $this->hasMany(StudentTransactions::class, 'student_id', 'id');
    }

    public function StudentTransact($type, $amount, $description): void
    {
        StudentTransactions::query()->create([
            'student_id' => $this->id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'balance' => $this->StudentTuition->balance + ($type === 'credit' ? $amount : -$amount),
            'date' => now(),
        ]);
    }

    // /get student picture_1x1
    public function getStudentPictureAttribute()
    {
        return $this->DocumentLocation->picture_1x1 ?? '';
    }

    // get Full name
    public function getFullNameAttribute(): string
    {
        $nameParts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name
        ]);

        return implode(' ', $nameParts) ?: 'Unknown Student';
    }

    //    transaction for students
    public function Transaction()
    {
        return $this->belongsToMany(Transaction::class, 'student_transactions', 'student_id', 'transaction_id');
    }

    public function getPicture1x1Attribute()
    {
        return $this->DocumentLocation->picture_1x1 ?? '';
    }

    public function hasRequestedEnrollment()
    {
        return $this->StudentTuition()
            ->where('semester', GeneralSettings::query()->first()->semester)
            ->where('school_year', GeneralSettings::query()->first()->getSchoolYear())
            ->exists();
    }

    public function getStudentChecklistAttribute()
    {
        return $this->subjectEnrolled()->get();
    }

    public function getTotalEnrolledSubjectsAttribute()
    {
        return $this->subjectEnrolled()->count();
    }

    public function getTotalUnitsAttribute()
    {
        return $this->subjectEnrolled()->sum('units');
    }

    public function getProfilePhotoUrlAttribute()
    {
        return $this->Accounts->profile_photo_url;
    }

    /**
     * Get the student's classes scheduled for today
     *
     * @param bool $withRelationships Whether to load relationships
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTodaysClasses($withRelationships = true)
    {
        // Get current day of week (0 = Sunday, 6 = Saturday)
        $today = now()->dayOfWeek;
        $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $todayName = $dayNames[$today];

        // Get current semester and school year from settings
        $settings = GeneralSettings::query()->first();
        $currentSemester = $settings->semester;
        $currentSchoolYear = $settings->getSchoolYear();

        // Build the query
        $query = Classes::query()
            ->whereHas('ClassStudents', function ($query) {
                $query->where('student_id', $this->id);
            })
            ->whereHas('Schedule', function ($query) use ($todayName) {
                $query->where('day_of_week', $todayName);
            })
            ->where('semester', $currentSemester)
            ->where('school_year', $currentSchoolYear);

        // Add relationships if requested
        if ($withRelationships) {
            $query->with([
                'Subject',
                'Faculty',
                'Schedule.room',
                'ShsSubject',
                'ClassStudents' => function ($query) {
                    $query->where('student_id', $this->id);
                }
            ]);
        }

        return $query->get();
    }

    /**
     * Get the student's current class (the one happening right now)
     *
     * @return \App\Models\Classes|null
     */
    public function getCurrentClass()
    {
        $currentTime = now();
        $todaysClasses = $this->getTodaysClasses();
        $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $todayName = $dayNames[now()->dayOfWeek];

        foreach ($todaysClasses as $class) {
            foreach ($class->Schedule as $schedule) {
                if ($schedule->day_of_week !== $todayName) {
                    continue;
                }

                $startTime = \Carbon\Carbon::parse($schedule->start_time);
                $endTime = \Carbon\Carbon::parse($schedule->end_time);

                if ($currentTime->between($startTime, $endTime)) {
                    return [
                        'class' => $class,
                        'schedule' => $schedule
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Format the student's classes for the dashboard
     *
     * @return array
     */
    public function getFormattedTodaysClasses()
    {
        $todaysClasses = $this->getTodaysClasses();
        $formattedClasses = [];
        $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $todayName = $dayNames[now()->dayOfWeek];

        foreach ($todaysClasses as $class) {
            // Get the schedules for today
            $todaySchedules = $class->Schedule->filter(function ($schedule) use ($todayName) {
                return $schedule->day_of_week == $todayName;
            });

            // Get enrollment data for this class
            $enrollment = $class->ClassStudents->first();

            // Process each schedule for this class
            foreach ($todaySchedules as $schedule) {
                $startTime = \Carbon\Carbon::parse($schedule->start_time)->format('g:i A');
                $endTime = \Carbon\Carbon::parse($schedule->end_time)->format('g:i A');

                $formattedClasses[] = [
                    'id' => $class->id,
                    'subject' => $class->subject_title,
                    'subject_code' => $class->formated_subject_code,
                    'room' => $schedule->room ? $schedule->room->name : $class->formated_assigned_rooms,
                    'time' => "$startTime - $endTime",
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'duration' => $schedule->formatted_duration,
                    'teacher' => $class->faculty_full_name,
                    'section' => $class->section,
                    'grade_status' => $enrollment ? $enrollment->grade_status : 'Pending',
                    'day_of_week' => $schedule->day_of_week,
                    'schedule_id' => $schedule->id,
                ];
            }
        }

        // Sort classes by start time
        usort($formattedClasses, function ($a, $b) {
            // Convert times to comparable format
            $timeA = strtotime(date('Y-m-d ') . str_replace(' ', '', $a['start_time']));
            $timeB = strtotime(date('Y-m-d ') . str_replace(' ', '', $b['start_time']));
            return $timeA - $timeB;
        });

        return $formattedClasses;
    }

    /**
     * Get the formatted current class data for the dashboard
     *
     * @return array|null
     */
    public function getFormattedCurrentClass()
    {
        $currentClassData = $this->getCurrentClass();
        if (!$currentClassData) {
            return null;
        }

        $currentClass = $currentClassData['class'];
        $currentSchedule = $currentClassData['schedule'];
        $currentTime = now();

        $startTime = \Carbon\Carbon::parse($currentSchedule->start_time);
        $endTime = \Carbon\Carbon::parse($currentSchedule->end_time);
        $timeRemaining = $currentTime->diffInMinutes($endTime);
        $totalDuration = $startTime->diffInMinutes($endTime);
        $progressPercentage = $totalDuration > 0
            ? round((($totalDuration - $timeRemaining) / $totalDuration) * 100)
            : 0;

        // Get enrollment data for this class
        $enrollment = $currentClass->ClassStudents->first();

        $formattedCurrentClass = [
            'id' => $currentClass->id,
            'subject' => $currentClass->subject_title,
            'subject_code' => $currentClass->formated_subject_code,
            'room' => $currentSchedule->room ? $currentSchedule->room->name : 'N/A',
            'time' => \Carbon\Carbon::parse($currentSchedule->start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($currentSchedule->end_time)->format('g:i A'),
            'teacher' => $currentClass->faculty_full_name,
            'section' => $currentClass->section,
            'time_remaining' => $timeRemaining,
            'progress' => $progressPercentage,
            'duration' => $currentSchedule->formatted_duration,
            'grade_status' => $enrollment ? $enrollment->grade_status : 'Pending',
        ];

        // Find the next class
        $todaysClasses = $this->getFormattedTodaysClasses();
        $currentEndTime = strtotime(date('Y-m-d ') . str_replace(' ', '', \Carbon\Carbon::parse($currentSchedule->end_time)->format('g:i A')));

        foreach ($todaysClasses as $index => $classData) {
            $classStartTime = strtotime(date('Y-m-d ') . str_replace(' ', '', $classData['start_time']));
            if ($classStartTime > $currentEndTime) {
                $nextClass = $classData;
                $nextStartTime = \Carbon\Carbon::createFromFormat('g:i A', $nextClass['start_time']);

                $formattedCurrentClass['next_class'] = [
                    'subject' => $nextClass['subject'],
                    'time' => $nextClass['time'],
                    'time_until' => $endTime->diffForHumans($nextStartTime, true)
                ];
                break;
            }
        }

        return $formattedCurrentClass;
    }

    protected static function boot(): void
    {
        parent::boot();
        // static::creating(function (Students $model) {
        //     $maxStudentId = Students::max('id');
        //     $newId = max($maxStudentId, 1) + 1;
        //     $model->student_id = $newId;
        // });

        self::forceDeleting(function ($student): void {
            $student->StudentTransactions()->delete();
            $student->StudentTuition()->delete();
            $student->StudentParentInfo()->delete();
            $student->StudentEducationInfo()->delete();
            $student->StudentContactsInfo()->delete();
            $student->personalInfo()->delete();
            $student->subjectEnrolled()->delete();
            $student->DocumentLocation()->delete();
            $student->Accounts()->delete();
        });
    }
}
