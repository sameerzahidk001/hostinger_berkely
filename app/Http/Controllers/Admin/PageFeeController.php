<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageFeeCategory;
use App\Models\PageFeePackage;
use App\Models\PageFeeService;
use App\Services\PageFeeStructureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PageFeeController extends Controller
{
    public function __construct(private PageFeeStructureService $fees)
    {
        $this->fees->ensureTables();
    }

    public function index()
    {
        $columns = ['id', 'page_name', 'url'];
        if (Schema::hasColumn('pages', 'fee_heading')) {
            $columns[] = 'fee_heading';
        }

        $pagesQuery = Page::query()->orderBy('page_name');
        if (Schema::hasColumn('pages', 'status')) {
            $pagesQuery->where('status', 1);
        }

        $pages = $pagesQuery->get($columns);

        $serviceCounts = collect();
        if (Schema::hasTable('page_fee_services')) {
            $serviceCounts = PageFeeService::query()
                ->selectRaw('page_id, COUNT(*) as total')
                ->groupBy('page_id')
                ->pluck('total', 'page_id');
        }

        return view('admin.pages.fees.index', compact('pages', 'serviceCounts'));
    }

    public function edit(string $pageId)
    {
        $page = Page::findOrFail($pageId);
        $services = PageFeeService::with(['categories.packages'])
            ->where('page_id', $page->id)
            ->orderBy('sort_order')
            ->get();

        return view('admin.pages.fees.edit', compact('page', 'services'));
    }

    public function updateHeading(Request $request, string $pageId)
    {
        $page = Page::findOrFail($pageId);
        $data = $request->validate([
            'fee_heading' => 'nullable|string|max:255',
        ]);

        if (Schema::hasColumn('pages', 'fee_heading')) {
            $page->fee_heading = $data['fee_heading'] ?? null;
            $page->save();
        }

        $this->logPageFeeActivity(
            'Page Fee Heading Updated',
            $page->page_name ?: ('Page #' . $page->id),
            $page->id
        );

        return redirect()->back()->with('success', 'Fee heading saved.');
    }

    public function storeService(Request $request, string $pageId)
    {
        $page = Page::findOrFail($pageId);
        $data = $this->validateService($request);

        PageFeeService::create([
            'page_id' => $page->id,
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_visible' => (string) ($data['is_visible'] ?? '1') === '1',
        ]);

        $this->logPageFeeActivity(
            'Page Fee Service Created',
            $this->pageFeeItemLabel($page->page_name, $data['name'] ?? null),
            $page->id
        );

        return redirect()->back()->with('success', 'Service added.');
    }

    public function updateService(Request $request, string $serviceId)
    {
        $service = PageFeeService::with('page')->findOrFail($serviceId);
        $data = $this->validateService($request);

        $service->update([
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_visible' => (string) ($data['is_visible'] ?? '1') === '1',
        ]);

        $this->logPageFeeActivity(
            'Page Fee Service Updated',
            $this->pageFeeItemLabel($service->page?->page_name, $data['name'] ?? $service->name),
            $service->page_id
        );

        return redirect()->back()->with('success', 'Service updated.');
    }

    public function storeCategory(Request $request, string $serviceId)
    {
        $service = PageFeeService::with('page')->findOrFail($serviceId);
        $data = $this->validateCategory($request);

        PageFeeCategory::create([
            'service_id' => $service->id,
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_visible' => (string) ($data['is_visible'] ?? '1') === '1',
        ]);

        $this->logPageFeeActivity(
            'Page Fee Category Created',
            $this->pageFeeItemLabel($service->page?->page_name, $service->name, $data['name'] ?? null),
            $service->page_id
        );

        return redirect()->back()->with('success', 'Category added.');
    }

    public function updateCategory(Request $request, string $categoryId)
    {
        $category = PageFeeCategory::with('service.page')->findOrFail($categoryId);
        $data = $this->validateCategory($request);

        $category->update([
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_visible' => (string) ($data['is_visible'] ?? '1') === '1',
        ]);

        $pageId = $category->service?->page_id;
        $this->logPageFeeActivity(
            'Page Fee Category Updated',
            $this->pageFeeItemLabel(
                $category->service?->page?->page_name,
                $category->service?->name,
                $data['name'] ?? $category->name
            ),
            $pageId
        );

        return redirect()->back()->with('success', 'Category updated.');
    }

    public function storePackage(Request $request, string $categoryId)
    {
        $category = PageFeeCategory::with('service.page')->findOrFail($categoryId);
        $payload = $this->packagePayload($request);
        $payload['category_id'] = $category->id;
        PageFeePackage::create($payload);

        $pageId = $category->service?->page_id;
        $this->logPageFeeActivity(
            'Fee Package Created',
            $this->pageFeeItemLabel(
                $category->service?->page?->page_name,
                $category->service?->name,
                $category->name,
                $payload['package_name'] ?? null
            ),
            $pageId
        );

        return redirect()->back()->with('success', 'Fee package added.');
    }

    public function updatePackage(Request $request, string $packageId)
    {
        $package = PageFeePackage::with('category.service.page')->findOrFail($packageId);
        $payload = $this->packagePayload($request);
        $package->update($payload);

        $pageId = $package->category?->service?->page_id;
        $this->logPageFeeActivity(
            'Fee Package Updated',
            $this->pageFeeItemLabel(
                $package->category?->service?->page?->page_name,
                $package->category?->service?->name,
                $package->category?->name,
                $payload['package_name'] ?? $package->package_name
            ),
            $pageId
        );

        return redirect()->back()->with('success', 'Fee package updated.');
    }

    public function duplicateService(string $serviceId)
    {
        $service = PageFeeService::with(['page', 'categories.packages'])->findOrFail($serviceId);
        $copy = $service->replicate();
        $copy->name = 'Copy of ' . $service->name;
        $copy->save();

        foreach ($service->categories as $category) {
            $catCopy = $category->replicate();
            $catCopy->service_id = $copy->id;
            $catCopy->save();
            foreach ($category->packages as $package) {
                $pkgCopy = $package->replicate();
                $pkgCopy->category_id = $catCopy->id;
                $pkgCopy->save();
            }
        }

        $this->logPageFeeActivity(
            'Page Fee Service Duplicated',
            $this->pageFeeItemLabel($service->page?->page_name, $copy->name),
            $service->page_id
        );

        return redirect()->back()->with('success', 'Service duplicated with categories and packages.');
    }

    public function duplicateCategory(string $categoryId)
    {
        $category = PageFeeCategory::with(['service.page', 'packages'])->findOrFail($categoryId);
        $copy = $category->replicate();
        $copy->name = 'Copy of ' . $category->name;
        $copy->save();

        foreach ($category->packages as $package) {
            $pkgCopy = $package->replicate();
            $pkgCopy->category_id = $copy->id;
            $pkgCopy->save();
        }

        $pageId = $category->service?->page_id;
        $this->logPageFeeActivity(
            'Page Fee Category Duplicated',
            $this->pageFeeItemLabel(
                $category->service?->page?->page_name,
                $category->service?->name,
                $copy->name
            ),
            $pageId
        );

        return redirect()->back()->with('success', 'Category duplicated with packages.');
    }

    public function destroyService(string $id)
    {
        $service = PageFeeService::with(['page', 'categories.packages'])->findOrFail($id);
        $pageId = $service->page_id;
        $label = $this->pageFeeItemLabel($service->page?->page_name, $service->name);

        foreach ($service->categories as $category) {
            $category->packages()->delete();
            $category->delete();
        }
        $service->delete();

        $this->logPageFeeActivity('Page Fee Service Deleted', $label, $pageId);

        return redirect()->back()->with('success', 'Service deleted.');
    }

    public function destroyCategory(string $id)
    {
        $category = PageFeeCategory::with(['service.page', 'packages'])->findOrFail($id);
        $pageId = $category->service?->page_id;
        $label = $this->pageFeeItemLabel(
            $category->service?->page?->page_name,
            $category->service?->name,
            $category->name
        );

        $category->packages()->delete();
        $category->delete();

        $this->logPageFeeActivity('Page Fee Category Deleted', $label, $pageId);

        return redirect()->back()->with('success', 'Category deleted.');
    }

    public function destroyPackage(string $id)
    {
        $package = PageFeePackage::with('category.service.page')->findOrFail($id);
        $pageId = $package->category?->service?->page_id;
        $label = $this->pageFeeItemLabel(
            $package->category?->service?->page?->page_name,
            $package->category?->service?->name,
            $package->category?->name,
            $package->package_name
        );

        $package->delete();

        $this->logPageFeeActivity('Fee Package Deleted', $label, $pageId);

        return redirect()->back()->with('success', 'Package deleted.');
    }

    private function pageFeeItemLabel(?string ...$parts): string
    {
        $clean = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part !== '') {
                $clean[] = $part;
            }
        }

        return $clean !== [] ? implode(' — ', $clean) : 'Page Fee';
    }

    private function logPageFeeActivity(string $action, string $item, $pageId = null): void
    {
        $url = $pageId
            ? route('admin.pages.fees.edit', $pageId)
            : route('admin.pages.fees.index');

        record_panel_activity($action, $item, $url);
    }

    private function validateService(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_visible' => 'nullable|in:0,1',
        ]);
    }

    private function validateCategory(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_visible' => 'nullable|in:0,1',
        ]);
    }

    private function packagePayload(Request $request): array
    {
        $data = $request->validate([
            'package_name' => 'required|string|max:255',
            'currency' => 'required|string|max:10',
            'price' => 'required|numeric|min:0',
            'tax_percentage' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'short_description' => 'nullable|string|max:2000',
            'key_point' => 'nullable|string|max:2000',
            'package_includes' => 'nullable|string|max:5000',
            'priority' => 'nullable|integer|min:0',
            'installments' => 'nullable|integer|min:0',
            'showonwebsite' => 'nullable|in:0,1',
            'status' => 'nullable|in:0,1',
        ]);

        $includes = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($data['package_includes'] ?? '')))));

        return [
            'package_name' => $data['package_name'],
            'currency' => $data['currency'],
            'price' => $data['price'],
            'tax_percentage' => $data['tax_percentage'] ?? null,
            'discount_amount' => $data['discount_amount'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'key_point' => $data['key_point'] ?? null,
            'package_includes' => $includes,
            'priority' => (int) ($data['priority'] ?? 0),
            'installments' => $data['installments'] ?? null,
            'showonwebsite' => (string) ($data['showonwebsite'] ?? '1') === '1',
            'status' => (string) ($data['status'] ?? '1') === '1',
        ];
    }
}
