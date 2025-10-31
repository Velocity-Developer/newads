<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FrasaExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Frasa',
            'Parent Term',
            'AI Result',
            'Input Google Status',
            'Notif Telegram',
            'Retry Count',
            'Created At',
        ];
    }

    public function map($frasa): array
    {
        return [
            $frasa->id,
            $frasa->frasa,
            optional($frasa->parentTerm)->terms,
            $frasa->hasil_cek_ai,
            $frasa->status_input_google,
            $frasa->notif_telegram,
            $frasa->retry_count,
            $frasa->created_at->format('Y-m-d H:i:s'),
        ];
    }
}