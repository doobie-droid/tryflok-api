<?php

namespace App\Exports;

use App\Models\ExternalCommunity;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Models\User;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExternalCommunitiesExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function query()
    {
        return ExternalCommunity::query()->where('user_id', $this->user->id);
    }

    public function map($externalCommunity): array
    {
        return [
            $externalCommunity->email,
            $externalCommunity->name,
            $externalCommunity->created_at,
        ];
    }

    public function headings(): array
    {
        return [
            'Email',
            'Name',
            'Date',
        ];
    }
}
