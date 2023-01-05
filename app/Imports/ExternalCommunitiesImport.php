<?php

namespace App\Imports;

use App\Models\ExternalCommunity;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Log;

class ExternalCommunitiesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    use Importable, SkipsFailures;
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new ExternalCommunity([
            "user_id" => $row['user_id'],
            "email" => $row['email'],
            "name" => $row['name'],
        ]);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'exists:users,id'],

             // Above is alias for as it always validates in batches
             '*.user_id' => ['required', 'string', 'exists:users,id'],

             'email' => ['required', 'string', 'email', 'max:255'],

             // Above is alias for as it always validates in batches
             '*.email' => ['required', 'string', 'email', 'max:255'],

             'name' => ['sometimes', 'string'],

             // Above is alias for as it always validates in batches
             '*.name' => ['sometimes', 'string'],
        ];
    }

    /**
     * @param Failure[] $failures
     */
    public function onFailure(Failure ...$failures)
    {
        Log::error($failures);
    }
}
