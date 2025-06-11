<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Office extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'acronym',
        'name',
        'head_name',
        'designation',
        'proposed_by',
        'approved_by',
        'proposed_at',
        'approved_at',
    ];

    protected $casts = [
        'proposed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
