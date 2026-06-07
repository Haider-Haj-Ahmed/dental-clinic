<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operatory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }
    public function providers(): HasMany    { return $this->hasMany(Provider::class); }
}
