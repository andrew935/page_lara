<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index()
    {
        $promotions = Promotion::query()
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.promotions.index', compact('promotions'));
    }

    public function create()
    {
        return view('admin.promotions.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'active' => ['nullable', 'boolean'],
        ]);

        $data['promo_plan_slug'] = 'max';
        $data['active'] = (bool) ($data['active'] ?? false);

        // Ensure only one active promo at a time (simple rule)
        if ($data['active']) {
            Promotion::query()->where('active', true)->update(['active' => false]);
        }

        Promotion::create($data);

        return redirect()->route('admin.promotions.index')->with('success', 'Promotion created.');
    }

    public function toggle(Promotion $promotion)
    {
        $newActive = !$promotion->active;
        if ($newActive) {
            Promotion::query()->where('active', true)->update(['active' => false]);
        }

        $promotion->update(['active' => $newActive]);

        return redirect()->route('admin.promotions.index')->with('success', 'Promotion updated.');
    }
}


