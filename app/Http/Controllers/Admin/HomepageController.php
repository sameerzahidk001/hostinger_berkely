<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\HomeSetting;
use Illuminate\Http\Request;
use App\Models\HomepageSection;
use App\Models\OurClient;
use Illuminate\Support\Facades\Storage;

class HomepageController extends Controller
{
    public function edit()
    {
        $sections = HomepageSection::all()->keyBy('section_name'); 
        $clients = Client::all();
        $banner = HomeSetting::first();
        // echo "<pre>";
        // print_r($sections->toArray());
        // exit;
        return view('admin.homepage.edit', compact('sections', 'banner', 'clients'));
    }

    public function bannerUpdate(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:50',
            'description' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'url_text' => 'nullable|string|max:50',
            'url' => 'nullable|url',
        ]);

        $banner = HomeSetting::first();

        if ($request->hasFile('image')) {
            $uploadedFile = $request->file('image');
            $filename = 'banner_' . time() . '.' . $uploadedFile->getClientOriginalExtension();
            $uploadedFile->move('images/banners', $filename);
            if ($banner && $banner->image != null && file_exists('images/banners/'. $banner->image)) {
                unlink('images/banners/'. $banner->image);
            }
            $data['image'] = $filename;
        }

        if ($banner) {
            $banner->update($data);
        } else {
            HomeSetting::create($data);
        }

        return redirect()->back()->with('success', 'Home settings updated successfully!');
    }

    public function update(Request $request)
    {
        $request->validate([
            'sections.*.title' => 'required|string|max:255',
            'sections.*.subtitle' => 'nullable|string|max:500',
            'sections.*.description' => 'nullable|string',
            'sections.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'sections.*.link' => 'nullable|string',
        ]);

        foreach ($request->sections as $sectionName => $data) {
            $section = HomepageSection::firstOrCreate(['section_name' => $sectionName]);

            if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
                // Delete old image if exists
                if (!empty($section->image) && file_exists(public_path($section->image))) {
                    unlink(public_path($section->image));
                }
            
                // Save new image in `public/frontend/homepage/`
                $file = $data['image'];
                $fileName = time() . '_' . $file->getClientOriginalName();
                $destinationPath = public_path('frontend/homepage');
                $file->move($destinationPath, $fileName);
            
                // Store relative path
                $data['image'] = 'frontend/homepage/' . $fileName;
            }

            $section->update($data);
        }
        return redirect()->route('homepage.edit')->with('success', 'Homepage updated successfully!');
    }
}
