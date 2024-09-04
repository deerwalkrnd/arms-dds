<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadingAssignment extends Model
{
    use HasFactory;
    protected $fillable = [
        "name",
        "date_assigned",
        "subject_teacher_id",
        "description",
        "reading_cas_type_id",
        "term_id",
        "submitted",
    ];

    public function readingCasType(): BelongsTo
    {
        return $this->belongsTo(ReadingCasType::class, "reading_cas_type_id");
    }

    public function subjectTeacher(): BelongsTo
    {
        return $this->belongsTo(SubjectTeacher::class, "subject_teacher_id");
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, "term_id");
    }

    public function readingCas(): HasMany
    {
        return $this->hasMany(ReadingCas::class, "readingAssignment_id");
    }
}