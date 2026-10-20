<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Page;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    // Show the list of menu items
    public function index()
    {
        $menus = Menu::with('parent')
            ->orderBy('menu_group')
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('menu_order')
            ->orderBy('name')
            ->get();
        $pages = Page::get();
        return view('admin.menu.index', compact('menus','pages'));
    }

    // Show the form to create a new menu item
    public function create()
    {
        return view('admin.menu.create');
    }

    public function store(Request $request)
{
    $request->validate([
        'menu_group' => 'required|string|max:255',
        'page_id' => 'nullable|exists:pages,id',
        'custom_name' => 'nullable|string|max:255',
        'custom_url' => 'nullable|string|max:2048',
        'menu_order' => 'nullable|integer',
        'parent_id' => 'nullable|exists:menus,id',
    ]);
    $parentId = $request->filled('parent_id') ? $request->input('parent_id') : null;
    // Determine if it's a custom menu or an existing page
    if ($request->filled('custom_name')) {
        $name = $request->input('custom_name');
        $link = $request->input('custom_url') ?: '#';
    } elseif ($request->filled('page_id')) {
        $page = Page::find($request->input('page_id'));
        $name = $request->input('page_name') ?: optional($page)->page_name;
        $link = $request->input('page_link')
            ? url('/' . ltrim($request->input('page_link'), '/'))
            : url('/' . ltrim((string) optional($page)->url, '/'));
    } else {
        return redirect()->back()->withInput()->withErrors(['custom_name' => 'Select a page or enter a custom menu name.']);
    }

    $existingMenu = Menu::where('menu_group', $request->menu_group)
                        ->where('name', $name)
                        ->where(function ($q) use ($parentId) {
                            if ($parentId) {
                                $q->where('parent_id', $parentId);
                            } else {
                                $q->whereNull('parent_id');
                            }
                        })
                        ->first();

    if ($existingMenu) {
        return redirect()->back()->withInput()->withErrors(['custom_name' => 'This menu already exists in the selected group.']);
    }

    $menu = new Menu([
        'menu_group' => $request->menu_group,
        'name' => $name,
        'link' => $link,
        'menu_order' => $request->menu_order,
        'is_active' => true,
        'parent_id' => $parentId,
    ]);

    $menu->save();

    return redirect()->route('menu.index')->with('success', 'Menu added successfully.');
}

public function update(Request $request, Menu $menu)
{
    $request->validate([
        'menu_group' => 'required|string|max:255',
        'page_id' => 'nullable|exists:pages,id',
        'custom_name' => 'nullable|string|max:255',
        'custom_url' => 'nullable|string|max:2048',
        'menu_order' => 'nullable|integer',
        'parent_id' => 'nullable|exists:menus,id',
        'is_active' => 'nullable|boolean',
    ]);

    if ($request->filled('custom_name')) {
        $menu->name = $request->input('custom_name');
        if ($request->filled('custom_url')) {
            $menu->link = $request->input('custom_url');
        }
    } elseif ($request->filled('page_id')) {
        $page = Page::find($request->input('page_id'));
        $menu->name = $request->input('page_name') ?: optional($page)->page_name ?: $menu->name;
        $pageLink = $request->input('page_link') ?: optional($page)->url;
        if ($pageLink) {
            $menu->link = url('/' . ltrim($pageLink, '/'));
        }
    }

    $menu->menu_group = $request->input('menu_group');
    $menu->menu_order = $request->input('menu_order');
    $menu->is_active = $request->input('is_active', true);
    $menu->parent_id = $request->filled('parent_id') ? $request->input('parent_id') : null;

    $menu->save();

    return redirect()->route('menu.index')->with('success', 'Menu item updated successfully.');
}

    // Show the form to edit an existing menu item
    public function edit(Menu $menu)
    {
        return view('admin.menu.edit', compact('menu'));
    }


    // Delete the menu item
    public function destroy(Menu $menu)
    {
        $menu->delete();

        return redirect()->route('menu.index')->with('success', 'Menu item deleted successfully');
    }
    public function activateGroup(Request $request, $menuGroup)
    {
        dd($request->all());
        // Deactivate all menu groups
        Menu::where('menu_group', '!=', $menuGroup)->update(['is_active' => 0]);
    
        // Activate or deactivate the selected menu group based on the request input
        Menu::where('menu_group', $menuGroup)->update(['is_active' => $request->input('is_active')]);
    
        return redirect()->route('menu.index')->with('success', 'Menu group status updated successfully.');
    }
}