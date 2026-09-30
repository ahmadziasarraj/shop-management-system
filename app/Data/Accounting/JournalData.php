<?php

namespace App\Data\Accounting;

use Illuminate\Support\Collection;

final readonly class JournalData
{
    /**
     * @param Collection<int, JournalLineData> $lines
     */
    public function __construct(
        public string $journalDate,
        public Collection $lines,
        public ?string $description = null,
        public ?string $referenceType = null,
        public ?string $referenceId = null,
        public ?string $createdBy = null,
    ) {
    }
}
