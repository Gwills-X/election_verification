<?php

namespace App\Services;

use App\Models\Election;
use App\Models\LocalGovernment;
use App\Models\State;
use App\Models\Ward;
use Illuminate\Support\Collection;

class ElectionGeographyOptions
{
    /**
     * @return array{states: Collection, lgas: Collection, wards: Collection, filters: array}
     */
    public function forElections(
        Collection $elections,
        ?Election $selectedElection,
        array $filters = []
    ): array {
        $scopeStateIds = $this->scopeStateIds($elections, $selectedElection);
        $stateOptions = State::query()
            ->whereIn('id', $scopeStateIds)
            ->orderBy('name')
            ->pluck('name');

        $filters = array_merge(
            ['state' => null, 'lga' => null, 'ward' => null],
            $filters
        );
        $filters['state'] = $this->canonicalOption($filters['state'], $stateOptions);

        $localGovernments = LocalGovernment::query()
            ->whereIn('state_id', $scopeStateIds)
            ->when(
                $filters['state'],
                fn ($query) => $query->whereHas(
                    'state',
                    fn ($stateQuery) => $stateQuery->where('name', $filters['state'])
                )
            )
            ->orderBy('name')
            ->get(['id', 'name', 'state_id']);
        $filters['lga'] = $this->canonicalOption(
            $filters['lga'],
            $localGovernments->pluck('name')
        );

        $wards = Ward::query()
            ->whereHas(
                'localGovernment',
                fn ($query) => $query
                    ->whereIn('state_id', $scopeStateIds)
                    ->when(
                        $filters['state'],
                        fn ($query) => $query->whereHas(
                            'state',
                            fn ($stateQuery) => $stateQuery->where(
                                'name',
                                $filters['state']
                            )
                        )
                    )
                    ->when(
                        $filters['lga'],
                        fn ($query) => $query->where(
                            'name',
                            $filters['lga']
                        )
                    )
            )
            ->orderBy('name')
            ->pluck('name');
        $filters['ward'] = $this->canonicalOption($filters['ward'], $wards);

        return [
            'states' => $stateOptions,
            'lgas' => $localGovernments->pluck('name')->unique()->values(),
            'wards' => $wards->unique()->values(),
            'filters' => $filters,
        ];
    }

    private function scopeStateIds(
        Collection $elections,
        ?Election $selectedElection
    ): array {
        $types = $selectedElection
            ? collect([$selectedElection->electionType])
            : $elections->pluck('electionType')->filter();

        if ($types->contains(fn ($type) => $type->scope === 'NATIONAL')) {
            return State::query()->orderBy('name')->pluck('id')->all();
        }

        return $types
            ->pluck('scope_state_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function canonicalOption(?string $value, Collection $options): ?string
    {
        if (!$value) {
            return null;
        }

        return $options->first(
            fn ($option) => mb_strtolower($option) === mb_strtolower($value)
        );
    }
}
