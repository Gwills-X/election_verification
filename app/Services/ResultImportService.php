<?php

namespace App\Services;

use App\Models\DatasetImport;
use App\Models\Election;
use App\Models\ElectionResult;
use App\Models\LocalGovernment;
use App\Models\Party;
use App\Models\PollingUnit;
use App\Models\State;
use App\Models\Ward;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ResultImportService
{
    public const REQUIRED_HEADERS = ['state', 'polling_unit_code', 'lga', 'ward'];

    public const KNOWN = [
        'state', 'polling_unit_code', 'polling_unit_name', 'lga', 'ward',
        'registered_voters', 'accredited_voters', 'rejected_votes', 'total_votes',
        'results_found', 'transcription_count', 'result_sheet_stamped',
        'result_sheet_corrected', 'result_sheet_invalid', 'result_sheet_unclear',
        'result_sheet_unsigned', 'results_file',
    ];

    private const HEADER_ALIASES = [
        'state' => ['state', 'state name', 'state_name'],
        'polling_unit_code' => ['polling_unit_code', 'polling unit code', 'pollingunitcode', 'pu code', 'pu-code', 'pu_code', 'pucode', 'polling code', 'polling-code', 'polling_code', 'code'],
        'polling_unit_name' => ['polling_unit_name', 'polling unit name', 'pollingunitname', 'pu name', 'pu-name', 'pu_name', 'puname', 'polling name', 'polling_name', 'name'],
        'lga' => ['lga', 'local government', 'local government area', 'local_government', 'local_government_area', 'localgovernment', 'localgovernmentarea'],
        'ward' => ['ward', 'ward name', 'ward_name', 'wardname'],
        'registered_voters' => ['registered_voters', 'registered voters', 'registeredvoters', 'registered voter', 'registered_voter', 'reg voters', 'reg_voters', 'regvoters'],
        'accredited_voters' => ['accredited_voters', 'accredited voters', 'accreditedvoters', 'accredited voter', 'accredited_voter', 'accredited'],
        'rejected_votes' => ['rejected_votes', 'rejected votes', 'rejectedvotes', 'rejected vote', 'rejected_vote', 'rejected'],
        'total_votes' => ['total_votes', 'total votes', 'totalvotes', 'total vote', 'total_vote', 'total valid votes', 'total_valid_votes', 'total valid'],
        'results_found' => ['results_found', 'results found', 'resultsfound'],
        'transcription_count' => ['transcription_count', 'transcription count', 'transcriptioncount'],
        'result_sheet_stamped' => ['result_sheet_stamped', 'result sheet stamped', 'resultsheetstamped'],
        'result_sheet_corrected' => ['result_sheet_corrected', 'result sheet corrected', 'resultsheetcorrected'],
        'result_sheet_invalid' => ['result_sheet_invalid', 'result sheet invalid', 'resultsheetinvalid'],
        'result_sheet_unclear' => ['result_sheet_unclear', 'result sheet unclear', 'resultsheetunclear'],
        'result_sheet_unsigned' => ['result_sheet_unsigned', 'result sheet unsigned', 'resultsheetunsigned'],
        'results_file' => ['results_file', 'results file', 'resultsfile', 'result file', 'results link', 'results_link', 'results url', 'results_url'],
    ];

    /**
     * Import a polling-unit dataset for an election.
     *
     * @return array Import summary shown to the user.
     */
    public function import(
    string $path,
    Election $election,
    string $datasetType = DatasetImport::TYPE_REAL,
    ?int $userId = null,
    ?string $electionResultType = null
): array{
        if (!is_readable($path)) {
            throw new InvalidArgumentException('The election result file could not be read.');
        }
$election->loadMissing('electionType.scopeState');

if (!$election->electionType) {
    throw new InvalidArgumentException(
        'The selected election does not have an election type configured.'
    );
}

$expectedElectionType = strtoupper(
    trim((string) $election->electionType->code)
);

$uploadedElectionType = strtoupper(
    trim((string) $electionResultType)
);

if ($uploadedElectionType === '') {
    throw new InvalidArgumentException(
        'Please specify the election type represented by the uploaded dataset.'
    );
}

if ($uploadedElectionType !== $expectedElectionType) {
    throw new InvalidArgumentException(
        "Dataset rejected. This dataset is identified as a "
        . "{$uploadedElectionType} Election result, but the selected election is "
        . "a {$expectedElectionType} Election."
    );
}

        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new InvalidArgumentException('The election result file could not be opened.');
        }

        $delimiter = $this->detectDelimiter($handle);
        rewind($handle);

        $rawHeaders = fgetcsv($handle, 0, $delimiter);
        if (!$rawHeaders || $rawHeaders === [null]) {
            fclose($handle);
            throw new InvalidArgumentException('The file is empty or does not contain a header row.');
        }

        $normalizedHeaders = $this->normalizeHeaders($rawHeaders);

        $missing = array_diff(self::REQUIRED_HEADERS, $normalizedHeaders);
        if ($missing) {
            fclose($handle);
            throw new InvalidArgumentException(
                'Missing required column(s): ' . implode(', ', $missing) .
                '. Detected columns: ' . implode(', ', $normalizedHeaders) .
                '. Expected examples include State, LGA, Ward, PU-Code, PU-Name.'
            );
        }

        $candidateColumns = array_values(array_diff($normalizedHeaders, self::KNOWN));

        $rows = [];
        $malformed = 0;
        while (($csvRow = fgetcsv($handle, 0, $delimiter)) !== false) {
            $hasValue = false;
            foreach ($csvRow as $value) {
                if (trim((string) $value) !== '') { $hasValue = true; break; }
            }
            if (!$hasValue) continue;
            if (count($csvRow) !== count($normalizedHeaders)) { $malformed++; continue; }
            $rows[] = array_combine($normalizedHeaders, array_map(fn ($v) => trim((string) $v), $csvRow));
        }
        fclose($handle);

        if (!$rows && !$malformed) {
            throw new InvalidArgumentException('The file contains no data rows.');
        }


        $stateCache = [];
        foreach ($rows as &$row) {
            $state = $this->resolveState($row['state'] ?? '', $stateCache);
            if (!$state) {
                throw new InvalidArgumentException(
                    "Unknown Nigerian state '{$row['state']}'. Check the State column and use a supported state name."
                );
            }

            if (
                $election->electionType->scope_state_id &&
                (int) $state->id !== (int) $election->electionType->scope_state_id
            ) {
                throw new InvalidArgumentException(
                    "{$election->electionType->name} results for this application must belong to {$election->electionType->scopeState->name}."
                );
            }

            $row['_state_id'] = $state->id;
        }
        unset($row);

        $codes = array_filter(array_column($rows, 'polling_unit_code'), fn ($c) => trim((string) $c) !== '');
        $counts = array_count_values($codes);
        $codes = array_values(array_unique($codes));
        $pollingUnitsByCode = [];
        foreach (array_chunk($codes, 1000) as $codeBatch) {
            foreach (PollingUnit::whereIn('code', $codeBatch)->get() as $pollingUnit) {
                $pollingUnitsByCode[mb_strtolower($pollingUnit->code)] = $pollingUnit;
            }
        }

        $hasAccredited = in_array('accredited_voters', $normalizedHeaders, true);
        $hasRegistered = in_array('registered_voters', $normalizedHeaders, true);
        $hasRejected = in_array('rejected_votes', $normalizedHeaders, true);
        $hasTotalVotes = in_array('total_votes', $normalizedHeaders, true);

        $validator = new ResultValidationService(
            candidateColumns: $candidateColumns,
            requiredFields: self::REQUIRED_HEADERS,
            hasAccredited: $hasAccredited,
            hasRegistered: $hasRegistered,
            hasRejected: $hasRejected,
            hasTotalVotes: $hasTotalVotes,
        );

        $existingResultsByCode = ElectionResult::with([
            'pollingUnit:id,code,ward_id',
            'candidateVotes.party',
        ])
            ->where('election_id', $election->id)
            ->whereHas('pollingUnit', fn ($query) => $query->whereIn('code', $codes))
            ->get()
            ->reduce(function (array $carry, ElectionResult $result): array {
                $code = trim((string) ($result->pollingUnit?->code ?? ''));
                if ($code !== '') {
                    $carry[$code] = $result;
                }
                return $carry;
            }, []);

        $import = DatasetImport::create([
            'election_id' => $election->id,
            'user_id' => $userId,
            'filename' => basename($path),
            'dataset_type' => $datasetType,
            'status' => DatasetImport::STATUS_FAILED,
        ]);

        $localGovernmentCache = [];
        $wardCache = [];
        $partyCache = [];

        return DB::transaction(function () use (
            $rows, $malformed, $counts, $validator, $election, $import,
            $candidateColumns, $hasAccredited, $hasTotalVotes, $existingResultsByCode,
            &$pollingUnitsByCode, &$localGovernmentCache, &$wardCache, &$partyCache
        ) {
            $summary = [
                'import_id' => $import->id,
                'found' => count($rows) + $malformed,
                'submitted' => count($rows),
                'imported' => 0,
                'valid' => 0,
                'invalid' => 0,
                'flagged' => 0,
                'new_records' => 0,
                'existing_records' => 0,
                'conflicts' => 0,
                'duplicates' => 0,
                'duplicate_rows' => 0,
                'skipped' => $malformed,
                'candidate_columns' => $candidateColumns,
                'rules_skipped' => [],
            ];
            $candidateVoteRows = [];
            $validationRows = [];
            $checkedAt = now();

            if (!$hasAccredited) $summary['rules_skipped'][] = 'Accredited-voter check was skipped because the dataset has no accredited_voters column.';
            if (!$hasTotalVotes) $summary['rules_skipped'][] = 'Total-votes comparison was skipped because the dataset has no total_votes column.';
            if (!$candidateColumns) $summary['rules_skipped'][] = 'Candidate-total check was skipped because no candidate/party columns were detected.';

            $processedCodes = [];
            foreach ($rows as $row) {
                $code = trim((string) ($row['polling_unit_code'] ?? ''));
                if ($code === '') { $summary['skipped']++; continue; }

                $codeKey = mb_strtolower($code);
                if (isset($processedCodes[$codeKey])) {
                    $summary['duplicate_rows']++;
                    $summary['skipped']++;
                    continue;
                }
                $processedCodes[$codeKey] = true;

                $duplicateInFile = ($counts[$code] ?? 0) > 1;
                $existingResult = $existingResultsByCode[$code] ?? null;

                if ($existingResult) {
                    if ($this->isExactDuplicate($existingResult, $row, $candidateColumns)) {
                        $summary['existing_records']++;
                        $summary['duplicates']++;
                        continue;
                    }

                    $summary['conflicts']++;
                    $summary['duplicates']++;
                    $summary['flagged']++;

                    $existingResult->update([
                        'validation_status' => ResultValidationService::FLAGGED,
                        'review_decision' => null,
                        'reviewed_at' => null,
                        'reviewed_by' => null,
                        'review_note' => $existingResult->review_note ?? 'Potential conflict detected during re-import.',
                    ]);

                    $validationRows[] = [
                        'election_result_id' => $existingResult->id,
                        'status' => ResultValidationService::FLAGGED,
                        'validation_rule' => 'RESULT_CONFLICT',
                        'validation_message' => 'The imported values conflict with the existing result for this polling-unit identity and require review before approval.',
                        'checked_at' => $checkedAt,
                    ];

                    continue;
                }

                $outcome = $validator->validate($row, $duplicateInFile, false, false);

                $pollingUnit = $this->resolvePollingUnit(
                    $row,
                    $code,
                    $localGovernmentCache,
                    $wardCache,
                    $pollingUnitsByCode
                );

                // Geography and election scope were checked for every row before this transaction.
                $result = ElectionResult::withoutEvents(fn () => ElectionResult::create([
                    'election_id' => $election->id,
                    'polling_unit_id' => $pollingUnit->id,
                    'registered_voters' => $this->integerValue($row['registered_voters'] ?? null),
                    'accredited_voters' => $this->integerValue($row['accredited_voters'] ?? null),
                    'rejected_votes' => $this->integerValue($row['rejected_votes'] ?? null),
                    'total_votes' => $this->integerValue($row['total_votes'] ?? null),
                    'validation_status' => $outcome['status'],
                    'source_type' => $import->dataset_type,
                    'dataset_import_id' => $import->id,
                ]));

                foreach ($candidateColumns as $candidate) {
                    $votes = $this->integerValue($row[$candidate] ?? null);
                    if ($votes === null) {
                        continue;
                    }

                    $party = $this->resolveParty($candidate, $partyCache);
                    $candidateVoteRows[] = [
                        'election_result_id' => $result->id,
                        'party_id' => $party->id,
                        'votes' => $votes,
                        'created_at' => $checkedAt,
                        'updated_at' => $checkedAt,
                    ];
                }

                $actionable = array_filter($outcome['violations'], fn ($v) =>
                    in_array($v['severity'], [ResultValidationService::INVALID, ResultValidationService::FLAGGED], true));

                if (!$actionable) {
                    $validationRows[] = [
                        'election_result_id' => $result->id,
                        'status' => ResultValidationService::VALID,
                        'validation_rule' => 'ALL_RULES',
                        'validation_message' => 'All applicable validation rules passed.',
                        'checked_at' => $checkedAt,
                    ];
                }

                foreach ($outcome['violations'] as $violation) {
                    $validationRows[] = [
                        'election_result_id' => $result->id,
                        'status' => $violation['severity'],
                        'validation_rule' => $violation['rule'],
                        'validation_message' => $violation['message'],
                        'checked_at' => $checkedAt,
                    ];
                }

                $summary['imported']++;
                $summary['new_records']++;
                match ($outcome['status']) {
                    ResultValidationService::VALID => $summary['valid']++,
                    ResultValidationService::INVALID => $summary['invalid']++,
                    ResultValidationService::FLAGGED => $summary['flagged']++,
                    default => null,
                };
            }

            foreach (array_chunk($candidateVoteRows, 500) as $voteBatch) {
                DB::table('election_result_candidate_votes')->insert($voteBatch);
            }

            foreach (array_chunk($validationRows, 500) as $validationBatch) {
                DB::table('validation_results')->insert($validationBatch);
            }

            $summary['duplicates'] = $summary['existing_records']
                + $summary['conflicts']
                + $summary['duplicate_rows'];
            $summary['status'] = $this->importStatus($summary);

            $import->update([
                'records_found' => $summary['found'],
                'records_submitted' => $summary['submitted'],
                'records_imported' => $summary['imported'],
                'new_records' => $summary['new_records'],
                'existing_records' => $summary['existing_records'],
                'conflicts' => $summary['conflicts'],
                'valid' => $summary['valid'],
                'invalid' => $summary['invalid'],
                'flagged' => $summary['flagged'],
                'duplicates' => $summary['duplicates'],
                'skipped' => $summary['skipped'],
                'candidate_columns' => $summary['candidate_columns'],
                'rules_skipped' => $summary['rules_skipped'],
                'status' => $summary['status'],
                'completed_at' => now(),
            ]);

            return $summary;
        });
    }

    private function importStatus(array $summary): string
    {
        if (($summary['conflicts'] ?? 0) > 0) {
            return DatasetImport::STATUS_COMPLETED_WITH_CONFLICTS;
        }

        if (($summary['new_records'] ?? 0) === 0 && ($summary['existing_records'] ?? 0) > 0) {
            return DatasetImport::STATUS_ALREADY_IMPORTED;
        }

        return DatasetImport::STATUS_COMPLETED;
    }

    private function isExactDuplicate(ElectionResult $existingResult, array $row, array $candidateColumns): bool
    {
        $candidateResults = [];
        foreach ($candidateColumns as $candidate) {
            $value = $row[$candidate] ?? null;
            $candidateResults[$this->partyCode($candidate)] = trim((string) $value) === ''
                ? null
                : $value;
        }

        $incoming = [
            'registered_voters' => $this->integerValue($row['registered_voters'] ?? null),
            'accredited_voters' => $this->integerValue($row['accredited_voters'] ?? null),
            'rejected_votes' => $this->integerValue($row['rejected_votes'] ?? null),
            'total_votes' => $this->integerValue($row['total_votes'] ?? null),
            'candidate_results' => $candidateResults,
        ];

        $existing = [
            'registered_voters' => $existingResult->registered_voters,
            'accredited_voters' => $existingResult->accredited_voters,
            'rejected_votes' => $existingResult->rejected_votes,
            'total_votes' => $existingResult->total_votes,
            'candidate_results' => $existingResult->candidateVotes
                ->mapWithKeys(fn ($vote) => [
                    $vote->party->code => $vote->votes,
                ])
                ->all(),
        ];

        return $this->normalizeComparableValues($existing) === $this->normalizeComparableValues($incoming);
    }

    private function normalizeComparableValues(array $payload): array
    {
        $normalized = [];
        foreach ($payload as $key => $value) {
            if ($key === 'candidate_results') {
                $normalized[$key] = [];
                foreach ((array) $value as $candidate => $votes) {
                    $normalized[$key][(string) $candidate] = $votes === null || $votes === '' ? null : (int) $votes;
                }
                ksort($normalized[$key]);
                continue;
            }

            $normalized[$key] = $value === null || $value === '' ? null : (int) $value;
        }

        return $normalized;
    }

    private function resolveState(string $value, array &$cache): ?State
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $key = mb_strtolower($value);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $code = strtoupper($value) === 'FCT' ? 'FC' : strtoupper($value);

        return $cache[$key] = State::query()
            ->where('country_code', 'NGA')
            ->where(fn ($query) => $query
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($value)])
                ->orWhere('code', $code))
            ->first();
    }

    private function resolvePollingUnit(
        array $row,
        string $code,
        array &$localGovernmentCache,
        array &$wardCache,
        array &$pollingUnitsByCode
    ): PollingUnit
    {
        $localGovernmentName = $this->nullableString($row['lga'] ?? null);
        $wardName = $this->nullableString($row['ward'] ?? null);
        if (!$localGovernmentName || !$wardName) {
            throw new InvalidArgumentException(
                'Every result must include a state, local government, ward, and polling-unit code.'
            );
        }

        $localGovernmentKey = $row['_state_id'] . ':' . mb_strtolower($localGovernmentName);
        $localGovernment = $localGovernmentCache[$localGovernmentKey] ?? null;
        if (!$localGovernment) {
            $localGovernment = LocalGovernment::query()
                ->where('state_id', $row['_state_id'])
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($localGovernmentName)])
                ->first();
        }

        if (!$localGovernment) {
            $localGovernment = LocalGovernment::create([
                'state_id' => $row['_state_id'],
                'name' => $localGovernmentName,
            ]);
        }
        $localGovernmentCache[$localGovernmentKey] = $localGovernment;

        $wardKey = $localGovernment->id . ':' . mb_strtolower($wardName);
        $ward = $wardCache[$wardKey] ?? null;
        if (!$ward) {
            $ward = Ward::query()
                ->where('local_government_id', $localGovernment->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($wardName)])
                ->first();
        }

        if (!$ward) {
            $ward = Ward::create([
                'local_government_id' => $localGovernment->id,
                'name' => $wardName,
            ]);
        }
        $wardCache[$wardKey] = $ward;

        $codeKey = mb_strtolower($code);
        $pollingUnit = $pollingUnitsByCode[$codeKey] ?? new PollingUnit(['code' => $code]);
        if ($pollingUnit->exists && $pollingUnit->ward_id && $pollingUnit->ward_id !== $ward->id) {
            throw new InvalidArgumentException(
                "Polling-unit code {$code} is already associated with a different geographic location."
            );
        }

        $name = $pollingUnit->name
            ?: $this->nullableString($row['polling_unit_name'] ?? null);
        if (!$pollingUnit->exists || $pollingUnit->ward_id !== $ward->id || $pollingUnit->name !== $name) {
            $pollingUnit->ward_id = $ward->id;
            $pollingUnit->name = $name;
            $pollingUnit->save();
        }
        $pollingUnitsByCode[$codeKey] = $pollingUnit;

        return $pollingUnit;
    }

    private function resolveParty(string $name, array &$cache): Party
    {
        $name = trim($name);
        $code = $this->partyCode($name);

        if (isset($cache[$code])) {
            return $cache[$code];
        }

        return $cache[$code] = Party::firstOrCreate(
            ['code' => $code],
            ['name' => $name]
        );
    }

    private function partyCode(string $name): string
    {
        return strtoupper(preg_replace('/[^a-z0-9]+/i', '', trim($name)));
    }

    private function detectDelimiter($handle): string
    {
        $position = ftell($handle);
        $firstLine = fgets($handle);
        fseek($handle, $position);
        if ($firstLine === false) return ',';
        $delimiters = [',' => substr_count($firstLine, ','), "\t" => substr_count($firstLine, "\t"),
                       ';' => substr_count($firstLine, ';'), '|' => substr_count($firstLine, '|')];
        arsort($delimiters);
        $delimiter = array_key_first($delimiters);
        return ($delimiter === null || $delimiters[$delimiter] === 0) ? ',' : $delimiter;
    }

    private function normalizeHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $header) {
            $original = trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $header));
            $normalized = $this->normalizeHeaderText($original);
            $mapped = null;
            foreach (self::HEADER_ALIASES as $applicationName => $aliases) {
                foreach ($aliases as $alias) {
                    if ($normalized === $this->normalizeHeaderText($alias)) { $mapped = $applicationName; break 2; }
                }
            }
            $result[] = $mapped ?? $this->cleanCandidateHeader($original);
        }
        if (count($result) !== count(array_unique($result))) {
            throw new InvalidArgumentException('The CSV contains duplicate or conflicting column names after normalization.');
        }
        return $result;
    }

    private function normalizeHeaderText(string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower(trim($value)));
    }

    private function cleanCandidateHeader(string $header): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', trim($header));
    }

    private function integerValue($value): ?int
    {
        if ($value === null || trim((string) $value) === '') return null;
        if (!is_numeric($value) || (float) $value != (int) $value) return null;
        return (int) $value;
    }

    private function nullableString($value): ?string
    {
        return ($value === null || trim((string) $value) === '') ? null : trim((string) $value);
    }
}
