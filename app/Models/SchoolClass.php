<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'level', 'position'])]
class SchoolClass extends Model
{
    protected $table = 'classes';

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'class_id')->orderBy('position');
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'class_id')->where('role', User::ROLE_STUDENT);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'class_id')->latest('id');
    }
}
