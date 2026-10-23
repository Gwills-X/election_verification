<?php
namespace App\Http\Requests\Admin;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in([User::ROLE_ELECTION_OFFICER, User::ROLE_OBSERVER])],
            'election_ids' => ['nullable', 'array'],
            'election_ids.*' => ['exists:elections,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
