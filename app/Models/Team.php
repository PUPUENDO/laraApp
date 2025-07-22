<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'workspace_id',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function users()
{
    return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
}


    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}