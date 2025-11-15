<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Merk extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'jenis_id'];

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(Jenis::class);
    }
}