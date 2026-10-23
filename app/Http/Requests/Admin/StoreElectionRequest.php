<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreElectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'election_type_id' => ['required', 'exists:election_types,id'],
            'year' => [
                'required',
                'integer',
                'min:1998',
                'max:2100',
                Rule::unique('elections', 'year')->where(
                    fn ($query) => $query->where(
                        'election_type_id',
                        $this->input('election_type_id')
                    )
                ),
            ],
        ];
    }
}
