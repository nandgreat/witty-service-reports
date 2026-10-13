<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Worker extends Model
{
    protected $guarded = [];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ChurchBranch::class, 'church_branch_id');
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
