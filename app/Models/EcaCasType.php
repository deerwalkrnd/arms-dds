<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcaCasType extends Model
{
    use HasFactory;
    protected $table = "eca_cas_types";
    protected $fillable = ["name", "school_id", "full_marks", "weightage"];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, "school_id");
    }

    public function ecaAssignments(): HasMany
    {
        return $this->hasMany(EcaAssignment::class, "eca_cas_type_id");
    }
    public function clubAssignments(): HasMany
    {
        return $this->hasMany(ClubAssignment::class, "eca_cas_type_id");
    }
}