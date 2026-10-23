<?php

namespace App\Http\Requests;

use App\Models\Election;
use Illuminate\Foundation\Http\FormRequest;

class ImportResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'election_id' => ['required', 'exists:elections,id'],
            'dataset_type' => ['required', 'in:REAL,SYNTHETIC'],
            'election_result_type' => ['required', 'in:PRESIDENTIAL,GOVERNORSHIP'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:51200'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $election = Election::with('electionType')->find($this->election_id);

            if (!$election || !$election->electionType) {
                return;
            }

            if (
                strtoupper($this->election_result_type) !==
                strtoupper($election->electionType->code)
            ) {
                $validator->errors()->add(
                    'election_result_type',
                    "This dataset is identified as a {$this->election_result_type} Election result, "
                    . "but the selected election is a {$election->electionType->code} Election."
                );
            }
        });
    }
}
