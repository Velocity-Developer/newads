<?php

namespace App\Http\Controllers;

use App\Models\BlacklistWord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class BlacklistWordController extends Controller
{
    public function index(Request $request)
    {
        $query = BlacklistWord::query();

        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }
        if ($request->filled('q')) {
            // Exact-case filtering untuk pencarian spesifik
            $query->where('word', $request->input('q'));
        }

        // Support per_page parameter
        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $items = $query->orderBy('word')->paginate($perPage)->withQueryString();

        return Inertia::render('BlacklistWords/Index', [
            'items' => $items,
            'filters' => [
                'active' => $request->input('active'),
                'q' => $request->input('q'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'word' => 'required|string|max:255',
            'active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);
        $data['word'] = trim($data['word']);

        BlacklistWord::create($data);
        Cache::forget('blacklist_words_active');

        return redirect()->back()->with('success', 'Kata blacklist ditambahkan.');
    }

    public function update(Request $request, BlacklistWord $blacklistWord)
    {
        $data = $request->validate([
            'word' => 'required|string|max:255',
            'active' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);
        $data['word'] = trim($data['word']);

        $blacklistWord->update($data);
        Cache::forget('blacklist_words_active');

        return redirect()->back()->with('success', 'Kata blacklist diperbarui.');
    }

    public function destroy(BlacklistWord $blacklistWord)
    {
        $blacklistWord->delete();
        Cache::forget('blacklist_words_active');

        return redirect()->back()->with('success', 'Kata blacklist dihapus.');
    }

    public function toggle(BlacklistWord $blacklistWord)
    {
        $blacklistWord->update(['active' => !$blacklistWord->active]);
        Cache::forget('blacklist_words_active');

        return redirect()->back()->with('success', 'Status aktif/nonaktif diubah.');
    }

    public function importLocal(Request $request)
    {
        $path = base_path('kata yang dikecualikan tahap 2.txt');
        if (!file_exists($path)) {
            return redirect()->back()->with('error', "File tidak ditemukan: {$path}");
        }

        $content = file_get_contents($path);
        $lines = preg_split("/\r\n|\n|\r/", $content);

        // Trim, buang kosong, unique exact-case
        $lines = array_map('trim', $lines);
        $lines = array_values(array_filter($lines, fn ($l) => $l !== ''));
        $unique = array_values(array_unique($lines, SORT_STRING));
        $total = count($unique);

        // Cek yang sudah ada (exact-case, kolom case-sensitive)
        $existing = BlacklistWord::whereIn('word', $unique)->pluck('word')->all();
        $toInsert = array_values(array_diff($unique, $existing));

        // Insert batch
        $now = now();
        $records = array_map(fn ($w) => [
            'word' => $w,
            'active' => true,
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $toInsert);

        if (!empty($records)) {
            BlacklistWord::insert($records);
        }

        Cache::forget('blacklist_words_active');

        $inserted = count($records);
        $duplicates = $total - $inserted;

        return redirect()->back()->with('success', "Import selesai. Total unik: {$total}, baru: {$inserted}, duplikat: {$duplicates}");
    }

    // Upload manual file .txt untuk import blacklist
    public function importUpload(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:txt', 'max:10240'], // 10MB
            'active' => ['nullable', 'boolean'],
        ]);

        $active = $request->boolean('active', true);
        $uploaded = $request->file('file');
        $content = file_get_contents($uploaded->getRealPath());

        $lines = preg_split("/\r\n|\n|\r/", $content);
        $lines = array_map('trim', $lines);
        $lines = array_values(array_filter($lines, fn ($l) => $l !== ''));

        // Deduplikasi exact-case agar konsisten dengan importLocal
        $unique = array_values(array_unique($lines, SORT_STRING));
        $total = count($unique);

        // Cari kata yang sudah ada
        $existing = BlacklistWord::whereIn('word', $unique)->pluck('word')->all();
        $toInsert = array_values(array_diff($unique, $existing));

        $now = now();
        $records = array_map(fn ($w) => [
            'word' => $w,
            'active' => $active,
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $toInsert);

        if (!empty($records)) {
            BlacklistWord::insert($records);
        }

        Cache::forget('blacklist_words_active');

        $inserted = count($records);
        $duplicates = $total - $inserted;

        return redirect()->back()->with('success', "Import upload selesai. Total unik: {$total}, baru: {$inserted}, duplikat: {$duplicates}");
    }
}