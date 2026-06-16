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
        $menus = Menu::orderBy('menu_order')->get();
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
        'custom_url' => 'nullable|url',
        'menu_order' => 'nullable|integer',
        'parent_id' => 'nullable|exists:menus,id',
    ]);
    // Determine if it's a custom menu or an existing page
    if ($request->filled('custom_name') && $request->filled('custom_url')) {
        $name = $request->input('custom_name');
        $link = $request->input('custom_url');
    } elseif ($request->filled('page_id')) {
        $page = Page::find($request->input('page_id'));
        $name = $request->input('page_name');
        $link = url('/' . ltrim($request->input('page_link'), '/'));
    } else {
        return redirect()->back()->withErrors('You must select a page or provide a custom menu.');
    }

    // Check if the menu already exists for the same page
    $existingMenu = Menu::where('menu_group', $request->menu_group)
                        ->where('name', $name)
                        ->first();

    if ($existingMenu) {
        return redirect()->back()->withErrors('This menu already exists in the selected group.');
    }

    // Create the new menu
    $menu = new Menu([
        'menu_group' => $request->menu_group,
        'name' => $name,
        'link' => $link,
        'menu_order' => $request->menu_order,
        'is_active' => true,
    ]);

    $menu->save();

    // Handle parent menu logic
    $menu->parent_id = $request->filled('parent_id') ? $request->input('parent_id') : null;
    $menu->save();

    return redirect()->route('menu.index')->with('success', 'Menu added successfully.');
}

public function update(Request $request, Menu $menu)
{
    $request->validate([
        'menu_group' => 'required|string|max:255',
        'page_id' => 'nullable|exists:pages,id',
        'custom_name' => 'nullable|string|max:255',
        'custom_url' => 'nullable|url',
        'menu_order' => 'nullable|integer',
        'parent_id' => 'nullable|exists:menus,id',
        'is_active' => 'nullable|boolean',
    ]);

    // Determine if it's a custom menu or an existing page
    if ($request->filled('custom_name') && $request->filled('custom_url')) {
        $menu->name = $request->input('custom_name');
        $menu->link = $request->input('custom_url');
    } elseif ($request->filled('page_id')) {
        $page = Page::find($request->input('page_id'));
        $name = $request->input('page_name');
        $link = url('/' . ltrim($request->input('page_link'), '/'));
    } else {
        return redirect()->back()->withErrors('You must select a page or provide a custom menu.');
    }

    // Update other fields
    $menu->menu_group = $request->input('menu_group');
    $menu->menu_order = $request->input('menu_order');
    $menu->is_active = $request->input('is_active', true);

    // Handle parent menu logic
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