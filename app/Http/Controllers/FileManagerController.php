<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class FileManagerController extends Controller
{
    public function index()
    {
        $basePath = public_path();
    
        $files = collect(File::allFiles($basePath))->filter(function ($file) {
            return in_array($file->getExtension(), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        });
    
        return view('admin.file-manager.index', compact('files'));
    }
    

public function store(Request $request)
{
    if ($request->hasFile('sections.image')) {
        // Handle Upload from Computer
        $file = $request->file('sections.image');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('images/library'), $fileName);
        persist_moved_upload(public_path('images/library'), $fileName);
    } elseif ($request->has('sections.selected_image')) {
        // Handle Selection from Public Folder
        $fileName = $request->input('sections.selected_image');
    } else {
        return back()->with('error', 'No image selected.');
    }

    // Save Image to Database (Modify as needed)
    Section::create([
        'image' => $fileName,
    ]);

    return back()->with('success', 'Image uploaded successfully!');
}


    // ✅ Upload file to the currently opened folder
    public function upload(Request $request, $folder = null)
    {
        $request->validate([
            'file' => function_exists('lms_image_upload_rule')
                ? lms_image_upload_rule('required')
                : 'required|file|mimes:jpg,jpeg,png,gif,webp,svg|max:300',
        ], [
            'file.max' => 'Image must be ' . (function_exists('lms_image_upload_max_kb') ? lms_image_upload_max_kb() : 300) . ' KB or smaller.',
        ]);

        $destinationPath = public_path($folder);

        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        $file = $request->file('file');
        if (function_exists('assert_image_upload_size')) {
            assert_image_upload_size($file);
        }
        $uploadedName = $file->getClientOriginalName();
        $file->move($destinationPath, $uploadedName);
        persist_moved_upload($destinationPath, $uploadedName);
   
        return back()->with('success', 'File uploaded successfully!');
    }

    // ✅ Delete a file
    public function destroy(Request $request)
    {
        $filePath = public_path($request->filename); // Ensure correct file path
        if (File::exists($filePath)) {
            File::delete($filePath);
            return back()->with('success', 'File deleted successfully!');
        }

        return back()->with('error', 'File not found.');
    }

    // ✅ Delete a folder and all its contents
    public function deleteFolder($folder)
    {
        $folderPath = public_path($folder);

        if (!File::exists($folderPath)) {
            return back()->with('error', 'Folder not found.');
        }

        File::deleteDirectory($folderPath); // ✅ Delete folder & its files/subfolders

        return back()->with('success', 'Folder deleted successfully!');
    }
}