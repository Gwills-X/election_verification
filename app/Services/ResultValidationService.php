<?php

namespace App\Services;

/**
 * Pure, deterministic rule-based validation engine.
 * No database access, no AI/ML. Neutral terminology only.
 */
class ResultValidationService
{
    public const PENDING = 'PENDING';
    public const VALID = 'VALID';
    public const INVALID = 'INVALID';
    public const FLAGGED = 'FLAGGED_FOR_REVIEW';
    public const NOT_APPLICABLE = 'NOT_APPLICABLE';

    public function __construct(
        private array $candidateColumns = [],
        private array $requiredFields = ['state', 'polling_unit_code', 'lga', 'ward'],
        private bool $hasAccredited = false,
        private bool $hasRegistered = false,
        private bool $hasRejected = false,
        private bool $hasTotalVotes = false,
    ) {}

    /**
     * @return array{status: string, violations: array}
     */
    public function validate(array $row, bool $isDuplicateInFile = false, bool $isDuplicateInDb = false, bool $isConflict = false): array
    {
        $violations = [];

        /* Rule 1 — Required fields (only when the column exists in the dataset) */
        foreach ($this->requiredFields as $field) {
            if (array_key_exists($field, $row) && trim((string) $row[$field]) === '') {
                $violations[] = $this->fail('REQUIRED_FIELD', "Required field '{$field}' is missing.");
            }
        }

        $numericFields = array_unique(array_merge(
            ['registered_voters', 'accredited_voters', 'rejected_votes', 'total_votes'],
            $this->candidateColumns
        ));

        /* Rules 2 & 3 — numeric fields must be whole numbers and not negative */
foreach ($numericFields as $field) {
    if (!array_key_exists($field, $row) || trim((string) $row[$field]) === '') {
        continue; // optional / absent field
    }

    if (!is_numeric($row[$field]) || (float) $row[$field] != (int) $row[$field]) {
        $violations[] = $this->fail(
            'DATA_TYPE',
            "Field '{$field}' must contain a whole number."
        );
        continue;
    }

    if ((int) $row[$field] < 0) {
        $violations[] = $this->fail(
            'NEGATIVE_VALUE',
            "Field '{$field}' cannot contain a negative vote count."
        );
    }
}

        /* Rule 4 — candidate votes must not exceed accredited voters */
        if ($this->hasAccredited) {
            $accredited = $this->num($row, 'accredited_voters');
            if ($accredited === null) {
                $violations[] = $this->na('ACCREDITED_VOTER_CHECK', 'Skipped: accredited-voter value is missing.');
            } else {
                $candidateSum = $this->candidateSum($row);
                if ($candidateSum !== null && $candidateSum > $accredited) {
                    $violations[] = $this->fail('ACCREDITED_VOTER_CHECK',
                        "Total candidate votes ({$candidateSum}) exceed accredited voters ({$accredited}).");
                }
            }
        } else {
            $violations[] = $this->na('ACCREDITED_VOTER_CHECK', 'Skipped: dataset has no accredited_voters column.');
        }

        /* Rule 5 — accredited voters must not exceed registered voters */
        if ($this->hasRegistered) {
            $accredited = $this->num($row, 'accredited_voters');
            $registered = $this->num($row, 'registered_voters');
            if ($accredited === null || $registered === null) {
                $violations[] = $this->na('REGISTERED_VOTER_CHECK', 'Skipped: registered/accredited voter value is missing.');
            } elseif ($accredited > $registered) {
                $violations[] = $this->fail('REGISTERED_VOTER_CHECK',
                    "Accredited voters ({$accredited}) exceed registered voters ({$registered}).");
            }
        } else {
            $violations[] = $this->na('REGISTERED_VOTER_CHECK', 'Skipped: dataset has no registered_voters column.');
        }

        /* Rule 6 — duplicate polling-unit record inside the uploaded file */
        if ($isDuplicateInFile) {
            $violations[] = $this->flag('DUPLICATE_RECORD', 'Duplicate polling-unit record detected within the dataset and requires human review.');
        }

        /* Rule 7 — polling-unit code already imported for this election */
        if ($isDuplicateInDb && !$isConflict) {
            $violations[] = $this->flag('DUPLICATE_CODE', 'Polling-unit code already exists for this election and requires human review.');
        }

        if ($isConflict) {
            $violations[] = $this->flag('RESULT_CONFLICT', 'The imported values conflict with an existing result for the same polling-unit identity and require review before approval.');
        }

        /* Rule 8 — geographic completeness */
        $geoMissing = array_filter(['state', 'lga', 'ward', 'polling_unit_code'], fn ($f) =>
            !array_key_exists($f, $row) || trim((string) $row[$f]) === '');
        if ($geoMissing) {
            $violations[] = $this->flag('GEOGRAPHIC_CONSISTENCY',
                'Incomplete geographic information: ' . implode(', ', $geoMissing) . '.');
        }

        /* Rule 9 — candidate votes + rejected votes = total votes (only when data exists) */
        if ($this->hasTotalVotes) {
            $totalVotes = $this->num($row, 'total_votes');
            if ($totalVotes === null) {
                $violations[] = $this->na('CANDIDATE_TOTAL_CHECK', 'Skipped: total_votes value is missing.');
            } else {
                $candidateSum = $this->candidateSum($row);
                if ($candidateSum === null) {
                    $violations[] = $this->na('CANDIDATE_TOTAL_CHECK', 'Skipped: no candidate/party columns were detected.');
                } else {
                    $rejected = $this->hasRejected ? ($this->num($row, 'rejected_votes') ?? 0) : 0;
                    $computed = $candidateSum + $rejected;
                    if ($computed !== $totalVotes) {
                        $violations[] = $this->fail('CANDIDATE_TOTAL_CHECK',
                            "Candidate/party votes plus rejected votes total {$computed}, but recorded total votes are {$totalVotes}.");
                    }
                }
            }
        } else {
            $violations[] = $this->na('CANDIDATE_TOTAL_CHECK', 'Skipped: dataset has no total_votes column.');
        }

        $severities = array_column($violations, 'severity');
        $status = in_array(self::INVALID, $severities, true) ? self::INVALID
            : (in_array(self::FLAGGED, $severities, true) ? self::FLAGGED : self::VALID);

        return ['status' => $status, 'violations' => $violations];
    }

    private function candidateSum(array $row): ?int
    {
        if (!$this->candidateColumns) return null;
        $sum = 0; $any = false;
        foreach ($this->candidateColumns as $col) {
            $v = $this->num($row, $col);
            if ($v !== null) { $sum += $v; $any = true; }
        }
        return $any ? $sum : null;
    }

    private function fail(string $rule, string $message): array
    { return ['rule' => $rule, 'message' => $message, 'severity' => self::INVALID]; }

    private function flag(string $rule, string $message): array
    { return ['rule' => $rule, 'message' => $message, 'severity' => self::FLAGGED]; }

    private function na(string $rule, string $message): array
    { return ['rule' => $rule, 'message' => $message, 'severity' => self::NOT_APPLICABLE]; }

    private function num(array $row, string $key): ?int
    {
        if (!array_key_exists($key, $row) || trim((string) $row[$key]) === '') return null;
        if (!is_numeric($row[$key]) || (float) $row[$key] != (int) $row[$key]) return null;
        return (int) $row[$key];
    }
}
