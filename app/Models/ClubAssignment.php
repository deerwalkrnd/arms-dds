<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClubAssignment extends Model
{
    use HasFactory;
    protected $fillable = [
        "name",
        "date_assigned",
        "subject_teacher_id",
        "description",
        "eca_cas_type_id",
        "term_id",
        "submitted",
    ];

    public function ecaCasType(): BelongsTo
    {
        return $this->belongsTo(EcaCasType::class, "eca_cas_type_id");
    }

    public function subjectTeacher(): BelongsTo
    {
        return $this->belongsTo(SubjectTeacher::class, "subject_teacher_id");
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, "term_id");
    }

    public function clubCas(): HasMany
    {
        return $this->hasMany(ClubCas::class, "clubAssignment_id");
    }
}