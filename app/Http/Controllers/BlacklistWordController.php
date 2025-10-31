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

        $items = $query->orderBy('word')->paginate(20)->withQueryString();

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
}