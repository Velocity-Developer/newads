DB::connection()->getDatabaseName(); // pastikan nama DB sesuai yang kamu lihat di GUI
App\Models\NewTermsNegative0Click::needsGoogleAdsInput()->count();
App\Models\NewTermsNegative0Click::needsGoogleAdsInput()
  ->select(['id','terms','hasil_cek_ai','status_input_google','retry_count','deleted_at'])
  ->orderBy('id','desc')->take(5)->get();