<?php

namespace App\Http\Controllers\Admin;

use App\Models\{Faq,Page};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FaqController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$faqs = get_page_faqs($request);
        $data['faqs'] = Faq::with('pages', 'createdBy', 'updatedBy')->get();
        $data['all_pages'] = Page::all();
        return view('admin.faq.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //return $request;
        $request->validate([
            'question' => 'required|unique:faqs',
            'answer' => 'required',
        ]);
        $data = $request->except('_token');
        $FaqCreated = Faq::create($data);
        
        if($FaqCreated){
            session()->flash('sucess', 'Record Added successfullly!');
            return redirect()->route('faq.index');
        }else{
            session()->flash('sucess', 'Failed to insert Record!');
            return redirect()->route('faq.index');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data['all_pages'] = Page::all();
        $data['faq'] = Faq::findOrFail($id);
        return view('admin.faq.edit')->with($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //return $request;
        $request->validate([
            'question' => 'required',
            'answer' => 'required',
        ]);
        $faq = Faq::findOrFail($id);
        $data = $request->except('_token');
        $FaqUpdated = $faq->update($data);
        
        if($FaqUpdated){
            session()->flash('sucess', 'Record updated successfullly!');
            return redirect()->route('faq.index');
        }else{
            session()->flash('sucess', 'Failed to update Record!');
            return redirect()->route('faq.index');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $faq = Faq::findOrFail($id);
        $result = $faq->delete();
        if ($result) {
            session()->flash('sucess', 'Record deleted successfullly!');
        } else {
            session()->flash('failed', 'Failed to delete Record');
        }
        
        return redirect()->route('faq.index');
    }
}
