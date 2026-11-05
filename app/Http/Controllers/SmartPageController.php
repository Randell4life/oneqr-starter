<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\SmartPage;
use App\Services\SmartPageResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SmartPageController extends Controller
{
    public function index(Request $request, Business $business): View
    {
        $this->authorizeOwner($request, $business);

        return view('business.smart-pages.index', [
            'business' => $business,
            'pages' => $business->smartPages()->withCount('blocks')->with('schedules')->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Business $business): RedirectResponse
    {
        $this->authorizeOwner($request, $business);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'kind' => ['required', Rule::in(['default', 'variant'])],
        ]);

        $slug = Str::slug($data['name']);
        if ($slug === '') {
            $slug = 'page';
        }
        $base = $slug;
        $suffix = 2;
        while ($business->smartPages()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        $page = DB::transaction(function () use ($business, $data, $slug): SmartPage {
            if ($data['kind'] === 'default') {
                $business->smartPages()->update(['is_default' => false]);
            }

            $page = $business->smartPages()->create([
                'name' => $data['name'],
                'slug' => $slug,
                'kind' => $data['kind'],
                'is_default' => $data['kind'] === 'default',
                'is_published' => false,
            ]);

            $page->blocks()->createMany([
                ['type' => 'heading', 'title' => $data['name'], 'content' => ['text' => $data['name']], 'sort_order' => 0],
                ['type' => 'text', 'title' => null, 'content' => ['text' => 'დაამატე შენი ბიზნესის ინფორმაცია.'], 'sort_order' => 1],
                ['type' => 'contact', 'title' => 'დაგვიკავშირდი', 'content' => [], 'sort_order' => 2],
            ]);

            return $page;
        });

        return redirect()->route('business.smart-pages.edit', [$business, $page])->with('status', 'გვერდი შეიქმნა. ახლა დაამატე ბლოკები და გამოაქვეყნე.');
    }

    public function edit(Request $request, Business $business, SmartPage $page): View
    {
        $this->authorizePage($request, $business, $page);

        return view('business.smart-pages.edit', compact('business', 'page'));
    }

    public function update(Request $request, Business $business, SmartPage $page): RedirectResponse
    {
        $this->authorizePage($request, $business, $page);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_published' => ['nullable', 'boolean'],
            'blocks' => ['required', 'array', 'min:1', 'max:30'],
            'blocks.*.type' => ['required', Rule::in(['heading', 'text', 'image', 'button', 'contact', 'catalog', 'hours'])],
            'blocks.*.title' => ['nullable', 'string', 'max:180'],
            'blocks.*.text' => ['nullable', 'string', 'max:5000'],
            'blocks.*.url' => ['nullable', 'url', 'max:2048', 'regex:/^https?:\/\//i'],
            'blocks.*.is_visible' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($page, $data): void {
            $page->update(['name' => $data['name'], 'is_published' => request()->boolean('is_published')]);
            $page->blocks()->delete();

            foreach (array_values($data['blocks']) as $position => $block) {
                $page->blocks()->create([
                    'type' => $block['type'],
                    'title' => $block['title'] ?? null,
                    'content' => array_filter(['text' => $block['text'] ?? null, 'url' => $block['url'] ?? null], fn ($value) => $value !== null && $value !== ''),
                    'is_visible' => (bool) ($block['is_visible'] ?? false),
                    'sort_order' => $position,
                ]);
            }
        });

        return back()->with('status', 'Smart Page განახლდა.');
    }

    public function destroy(Request $request, Business $business, SmartPage $page): RedirectResponse
    {
        $this->authorizePage($request, $business, $page);
        abort_if($page->is_default, 422, 'ძირითადი გვერდის წაშლა შეუძლებელია.');
        $page->delete();

        return back()->with('status', 'გვერდი წაიშალა.');
    }

    public function storeSchedule(Request $request, Business $business, SmartPage $page): RedirectResponse
    {
        $this->authorizePage($request, $business, $page);
        abort_unless($page->is_published, 422, 'განრიგისთვის ჯერ გამოაქვეყნე გვერდი.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'between:0,6'],
            'starts_at' => ['nullable', 'date_format:H:i', 'required_with:ends_at'],
            'ends_at' => ['nullable', 'date_format:H:i', 'required_with:starts_at'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'priority' => ['required', 'integer', 'between:1,100'],
            'is_override' => ['nullable', 'boolean'],
        ]);

        $business->smartPageSchedules()->create([
            ...$data,
            'smart_page_id' => $page->id,
            'weekdays' => $data['weekdays'] ?? [],
            'is_override' => $request->boolean('is_override'),
            'is_active' => true,
        ]);

        return back()->with('status', 'ავტომატური განრიგი დაემატა.');
    }

    public function destroySchedule(Request $request, Business $business, int $schedule): RedirectResponse
    {
        $this->authorizeOwner($request, $business);
        $business->smartPageSchedules()->whereKey($schedule)->firstOrFail()->delete();

        return back()->with('status', 'განრიგი წაიშალა.');
    }

    private function authorizeOwner(Request $request, Business $business): void
    {
        abort_unless((int) $business->owner_id === (int) $request->user()->id, 403);
    }

    private function authorizePage(Request $request, Business $business, SmartPage $page): void
    {
        $this->authorizeOwner($request, $business);
        abort_unless((int) $page->business_id === (int) $business->id, 404);
    }
}
