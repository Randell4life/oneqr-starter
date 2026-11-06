<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    private const CATEGORIES = [
        'general' => 'ზოგადი კითხვა',
        'billing' => 'გადახდა და გამოწერა',
        'qr' => 'QR კოდები და Smart Page',
        'orders' => 'შეკვეთები და კატალოგი',
        'technical' => 'ტექნიკური პრობლემა',
    ];

    private const STATUSES = [
        'open' => 'ღია',
        'in_progress' => 'მუშავდება',
        'waiting_customer' => 'მომხმარებლის პასუხი',
        'resolved' => 'მოგვარებული',
        'closed' => 'დახურული',
    ];

    public function index(Request $request): View
    {
        $tickets = SupportTicket::query()
            ->with(['business:id,name', 'messages'])
            ->where('created_by_user_id', $request->user()->id)
            ->withCount('messages')
            ->latest('last_reply_at')
            ->latest()
            ->paginate(15);

        return view('support.index', compact('tickets'));
    }

    public function create(Request $request): View
    {
        $businesses = Business::query()
            ->where('owner_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('support.create', [
            'businesses' => $businesses,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'business_id' => [
                'nullable',
                'integer',
                Rule::exists('businesses', 'id')->where('owner_id', $request->user()->id),
            ],
            'subject' => ['required', 'string', 'min:5', 'max:160'],
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'priority' => ['required', Rule::in(['normal', 'high'])],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $ticket = SupportTicket::query()->create([
            'business_id' => $data['business_id'] ?? null,
            'created_by_user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'category' => $data['category'],
            'priority' => $data['priority'],
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $data['message'],
        ]);

        return redirect()->route('support.show', $ticket)->with('status', 'მოთხოვნა წარმატებით გაიგზავნა. პასუხს ამავე გვერდზე მიიღებ.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $this->authorizeTicket($request, $ticket);

        $ticket->load(['business:id,name', 'creator:id,name,email', 'messages.user:id,name,is_platform_admin']);

        return view('support.show', [
            'ticket' => $ticket,
            'statuses' => self::STATUSES,
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorizeTicket($request, $ticket);
        abort_if(in_array($ticket->status, ['closed', 'resolved'], true) && ! $request->user()->is_platform_admin, 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $isAdmin = (bool) $request->user()->is_platform_admin;

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $data['message'],
        ]);

        $ticket->update([
            'last_reply_at' => now(),
            'status' => $isAdmin ? 'waiting_customer' : 'open',
            'closed_at' => null,
        ]);

        return back()->with('status', 'პასუხი დაემატა.');
    }

    public function platformIndex(Request $request): View
    {
        $this->authorizePlatformAdmin($request);

        $status = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(self::STATUSES))],
            'q' => ['nullable', 'string', 'max:160'],
        ]);

        $tickets = SupportTicket::query()
            ->with(['creator:id,name,email', 'business:id,name'])
            ->withCount('messages')
            ->when($status['status'] ?? null, fn (Builder $query, string $value) => $query->where('status', $value))
            ->when(trim((string) ($status['q'] ?? '')) !== '', function (Builder $query) use ($status): void {
                $term = trim($status['q']);
                $query->where(function (Builder $match) use ($term): void {
                    $match->where('subject', 'like', '%'.$term.'%')
                        ->orWhereHas('creator', fn (Builder $creator) => $creator
                            ->where('name', 'like', '%'.$term.'%')
                            ->orWhere('email', 'like', '%'.$term.'%'))
                        ->orWhereHas('business', fn (Builder $business) => $business->where('name', 'like', '%'.$term.'%'));
                });
            })
            ->orderByRaw("CASE priority WHEN 'high' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE status WHEN 'open' THEN 0 WHEN 'waiting_customer' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END")
            ->latest('last_reply_at')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'open' => SupportTicket::where('status', 'open')->count(),
            'waiting' => SupportTicket::where('status', 'waiting_customer')->count(),
            'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
            'resolved' => SupportTicket::whereIn('status', ['resolved', 'closed'])->count(),
        ];

        return view('platform.support.index', [
            'tickets' => $tickets,
            'summary' => $summary,
            'statuses' => self::STATUSES,
            'filters' => $status,
        ]);
    }

    public function platformUpdate(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorizePlatformAdmin($request);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'priority' => ['required', Rule::in(['normal', 'high'])],
        ]);

        $ticket->update([
            'status' => $data['status'],
            'priority' => $data['priority'],
            'closed_at' => in_array($data['status'], ['resolved', 'closed'], true) ? ($ticket->closed_at ?? now()) : null,
        ]);

        return back()->with('status', 'მოთხოვნის სტატუსი განახლდა.');
    }

    private function authorizeTicket(Request $request, SupportTicket $ticket): void
    {
        abort_unless(
            (bool) $request->user()->is_platform_admin
            || (int) $ticket->created_by_user_id === (int) $request->user()->id,
            403
        );
    }

    private function authorizePlatformAdmin(Request $request): void
    {
        abort_unless((bool) $request->user()?->is_platform_admin, 403);
    }
}
