<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageFeeCategory;
use App\Models\PageFeePackage;
use App\Models\PageFeeService;
use App\Services\PageFeeStructureService;
use Illuminate\Http\Request;

class PageFeeController extends Controller
{
    public function __construct(private PageFeeStructureService $fees)
    {
        $this->fees->ensureTables();
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

    public function storeService(Request $request, string $pageId)
    {
        $page = Page::findOrFail($pageId);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_visible' => 'nullable|in:0,1',
        ]);

        PageFeeService::create([
            'page_id' => $page->id,
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_visible' => (string) ($data['is_visible'] ?? '1') === '1',
        ]);

        return redirect()->back()->with('success', 'Service added.');
    }

    public function storeCategory(Request $request, string $serviceId)
    {
        $service = PageFeeService::findOrFail($serviceId);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_visible' => 'nullable|in:0,1',
        ]);

        PageFeeCategory::create([
            'service_id' => $service->id,
            'name' => $data['name'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_visible' => (string) ($data['is_visible'] ?? '1') === '1',
        ]);

        return redirect()->back()->with('success', 'Category added.');
    }

    public function storePackage(Request $request, string $categoryId)
    {
        $category = PageFeeCategory::findOrFail($categoryId);
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

        PageFeePackage::create([
            'category_id' => $category->id,
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
        ]);

        return redirect()->back()->with('success', 'Fee package added.');
    }

    public function duplicateService(string $serviceId)
    {
        $service = PageFeeService::with(['categories.packages'])->findOrFail($serviceId);
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

        return redirect()->back()->with('success', 'Service duplicated with categories and packages.');
    }

    public function duplicateCategory(string $categoryId)
    {
        $category = PageFeeCategory::with('packages')->findOrFail($categoryId);
        $copy = $category->replicate();
        $copy->name = 'Copy of ' . $category->name;
        $copy->save();

        foreach ($category->packages as $package) {
            $pkgCopy = $package->replicate();
            $pkgCopy->category_id = $copy->id;
            $pkgCopy->save();
        }

        return redirect()->back()->with('success', 'Category duplicated with packages.');
    }

    public function destroyService(string $id)
    {
        $service = PageFeeService::with('categories.packages')->findOrFail($id);
        foreach ($service->categories as $category) {
            $category->packages()->delete();
            $category->delete();
        }
        $service->delete();

        return redirect()->back()->with('success', 'Service deleted.');
    }

    public function destroyCategory(string $id)
    {
        $category = PageFeeCategory::with('packages')->findOrFail($id);
        $category->packages()->delete();
        $category->delete();

        return redirect()->back()->with('success', 'Category deleted.');
    }

    public function destroyPackage(string $id)
    {
        PageFeePackage::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Package deleted.');
    }
}
