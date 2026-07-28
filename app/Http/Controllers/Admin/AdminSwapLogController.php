<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SwapRequest;
use Illuminate\Http\Request;

class AdminSwapLogController extends Controller
{
    /**
     * List swap requests, optionally filtered by status.
     */
    public function index(Request $request)
    {
        $filter = $request->input('filter');

        $swaps = SwapRequest::query()
            ->when($filter, fn ($q) => $q->where('status', $filter))
            ->with(['fromClaim.student.user', 'fromClaim.dutySlot', 'toClaim.student.user', 'toClaim.dutySlot', 'fromStudent.user', 'toStudent.user'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Prepare swaps data for JavaScript
        $swapsData = $swaps->keyBy('id')->map(function ($swap) use ($swaps) {
            // Find display index (pagination number)
            $displayIndex = $swaps->firstItem() + $swaps->search(function ($item) use ($swap) {
                return $item->id === $swap->id;
            });
            
            return [
                'id' => $swap->id,
                'display_id' => $displayIndex,
                'status' => $swap->status->value,
                'from_student' => $swap->fromStudent?->user?->name ?? '-',
                'from_email' => $swap->fromStudent?->user?->email ?? '',
                'from_date' => $swap->fromClaim?->dutySlot?->duty_date ? \Illuminate\Support\Carbon::parse($swap->fromClaim->dutySlot->duty_date)->locale('id')->translatedFormat('l, d M Y') : '-',
                'from_time' => $swap->fromClaim?->dutySlot?->time_start ? $swap->fromClaim->dutySlot->time_start . ' – ' . $swap->fromClaim->dutySlot->time_end : '',
                'to_student' => $swap->toStudent?->user?->name ?? '-',
                'to_email' => $swap->toStudent?->user?->email ?? '',
                'to_date' => $swap->toClaim?->dutySlot?->duty_date ? \Illuminate\Support\Carbon::parse($swap->toClaim->dutySlot->duty_date)->locale('id')->translatedFormat('l, d M Y') : '-',
                'to_time' => $swap->toClaim?->dutySlot?->time_start ? $swap->toClaim->dutySlot->time_start . ' – ' . $swap->toClaim->dutySlot->time_end : '',
                'created_at' => $swap->created_at->format('d M Y H:i'),
                'responded_at' => $swap->responded_at ? $swap->responded_at->diffForHumans() : '—',
            ];
        });

        return view('admin.swap-logs.index', compact('swaps', 'filter', 'swapsData'));
    }
}
