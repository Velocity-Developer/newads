<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TermsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
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
            'Terms',
            'AI Result',
            'Input Google Status',
            'Notif Telegram',
            'Retry Count',
            'Created At',
        ];
    }

    public function map($term): array
    {
        return [
            $term->id,
            $term->terms,
            $term->hasil_cek_ai,
            $term->status_input_google,
            $term->notif_telegram,
            $term->retry_count,
            $term->created_at->format('Y-m-d H:i:s'),
        ];
    }
}