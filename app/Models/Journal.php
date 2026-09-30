<?php

namespace App\Models;

use App\Enums\JournalStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return [
            'journal_date' => 'date',
            'status' => JournalStatus::class
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }
}
