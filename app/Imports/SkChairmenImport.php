<?php

namespace App\Imports;

use App\Models\Barangay;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SkChairmenImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * Map each CSV row to a new User instance.
     */
    public function model(array $row)
    {
        $barangayName = trim((string) ($row['barangay'] ?? ''));
        
        // Find matching barangay by name
        $barangay = Barangay::where('barangay_name', 'LIKE', '%' . $barangayName . '%')->first();

        return new User([
            'first_name'   => trim($row['first_name']),
            'last_name'    => trim($row['last_name']),
            'email'        => trim($row['email']),
            'phone_number' => !empty($row['phone_number']) ? trim($row['phone_number']) : null,
            'password'     => Hash::make('SkLipa2026!'), // Default temporary password
            'barangay_id'  => $barangay?->barangay_id,
            'role'         => 'sk_chairman',
            'is_verified'  => true,
            'status'       => 'active',
            'profile_pic'  => null,
        ]);
    }

    /**
     * Validation rules for each row in the file.
     */
    public function rules(): array
    {
        return [
            '*.first_name'   => ['required', 'string', 'max:255'],
            '*.last_name'    => ['required', 'string', 'max:255'],
            '*.email'        => ['required', 'email', 'max:255', 'unique:users,email'],
            '*.barangay'     => ['required', 'string'],
            '*.phone_number' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Custom field names for error messages returned to the session.
     */
    public function customValidationAttributes(): array
    {
        return [
            '*.first_name' => 'First Name',
            '*.last_name'  => 'Last Name',
            '*.email'      => 'Email Address',
            '*.barangay'   => 'Barangay Name',
        ];
    }
}