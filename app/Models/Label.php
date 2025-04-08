<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Label extends Pivot
{
    use HasUlids;

    protected $table = 'labels';

    public function documents(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function tags(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
