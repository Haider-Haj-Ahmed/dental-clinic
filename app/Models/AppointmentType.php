<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'default_duration_minutes', 'color', 'is_active'];

    protected function casts(): array
    {
        return [
            'default_duration_minutes' => 'integer',
            'is_active'                => 'boolean',
        ];
    }

    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }
}
