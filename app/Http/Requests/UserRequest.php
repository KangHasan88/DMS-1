<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user') ? $this->route('user')->id : null;

        $rules = [
            // Informasi Dasar
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($userId)
            ],
            'username' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('users')->ignore($userId)
            ],
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|image|max:2048|mimes:jpeg,png,jpg',
            
            // Informasi Pribadi
            'gender' => 'nullable|in:male,female',
            'birth_date' => 'nullable|date|before:today',
            'address' => 'nullable|string|max:500',
            
            // Informasi Pekerjaan
            'employee_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users')->ignore($userId)
            ],
            'position' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'join_date' => 'nullable|date',
            'supervisor_id' => 'nullable|exists:users,id',
            
            // ROLE VALIDATION - PERBAIKAN DISINI
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ];

        // Password rules
        if ($this->isMethod('POST')) {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        } else {
            $rules['password'] = ['nullable', 'confirmed', Password::defaults()];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap harus diisi.',
            'email.required' => 'Email harus diisi.',
            'email.unique' => 'Email sudah digunakan.',
            'username.required' => 'Username harus diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip, dan underscore.',
            'roles.required' => 'Role harus dipilih.',
            'roles.array' => 'Format role tidak valid.',
            'roles.*.exists' => 'Role yang dipilih tidak valid.',
            'password.required' => 'Password harus diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.max' => 'Ukuran gambar maksimal 2MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Handle roles - pastikan roles berupa array
        if ($this->has('roles') && is_string($this->roles)) {
            $this->merge([
                'roles' => [$this->roles]
            ]);
        }
        
        // Auto-generate username dari email jika tidak diisi
        if ($this->isMethod('POST') && $this->has('email') && !$this->filled('username')) {
            $username = explode('@', $this->email)[0];
            $username = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);
            
            $this->merge([
                'username' => $username
            ]);
        }
    }
}