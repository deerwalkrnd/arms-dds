<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadingCasType extends Model
{
    use HasFactory;
    protected $table = "reading_cas_types";
    protected $fillable = ["name", "school_id", "full_marks", "weightage"];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, "school_id");
    }

    public function readingAssignments(): HasMany
    {
        return $this->hasMany(ReadingAssignment::class, "reading_cas_type_id");
    }
}