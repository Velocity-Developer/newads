<?php

namespace App\Services\SearchTermsAds;

use App\Models\SearchTerm;
use Illuminate\Support\Facades\DB;

class AnalyticServices
{
    /**
     * Hitung total berdasarkan status check_ai
     *
     * @return \Illuminate\Support\Collection
     */
    public function getTotalByCheckAi()
    {
        return SearchTerm::select('check_ai', DB::raw('count(*) as total'))
            ->groupBy('check_ai')
            ->get();
    }

    /**
     * Hitung total berdasarkan status iklan_dibuat
     *
     * @return \Illuminate\Support\Collection
     */
    public function getTotalByIklanDibuat()
    {
        return SearchTerm::select('iklan_dibuat', DB::raw('count(*) as total'))
            ->groupBy('iklan_dibuat')
            ->get();
    }

    /**
     * Hitung total data baru berdasarkan tanggal (created_at)
     *
     * @param  string|null  $startDate  Format: Y-m-d
     * @param  string|null  $endDate  Format: Y-m-d
     * @return \Illuminate\Support\Collection
     */
    public function getTotalNewDataByDate($startDate = null, $endDate = null)
    {
        $query = SearchTerm::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as total')
        );

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'desc')
            ->get();
    }
}
