<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Faq,Page};
use Illuminate\Support\Facades\Validator;

class FaqController extends Controller
{
    function index(Request $request){

        //$url = $request->url();
        $faqs = get_page_faqs($request);
        //return Page::with('faqs')->get();
        // $data['faqs'] = Faq::with('pages')->get();
        // return view('admin.faq.index')->with($data);
        $faqs = Faq::with('pages')->get()->groupBy('page_id');
        $all_pages = Page::all();
        return view('admin.faq.index', ['groupedFaqs' => $faqs, 'all_pages' => $all_pages]);
    }

    function FAQPages(){

        $data['pages'] = Page::with('faqs')->get();
        return view('admin.faq.pages')->with($data);
       
    }

    function StoreFAQPage(Request $request){

        $validator = Validator::make($request->all(), [
            'page_name' => 'required|unique:pages',
            
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()
            ->withErrors($validator)
            ->withInput();
        }

        $data = $request->except('_token');
        $pageCreated = Page::create($data);
        
        if($pageCreated){
            session()->flash('sucess', 'Record Added successfullly!');
            return redirect()->route('admin.faqs-pages');
        }else{
            session()->flash('sucess', 'Failed to insert Record!');
            return redirect()->route('admin.faqs-pages');
        }
    }

    function store(Request $request){
        //return $request;
        $validator = Validator::make($request->all(), [
            'question' => 'required|unique:faqs',
            'answer' => 'required',
            
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()
            ->withErrors($validator)
            ->withInput();
        }

        $data = $request->except('_token');
        $FaqCreated = Faq::create($data);
        
        if($FaqCreated){
            session()->flash('sucess', 'Record Added successfullly!');
            return redirect()->route('admin.faqs');
        }else{
            session()->flash('sucess', 'Failed to insert Record!');
            return redirect()->route('admin.faqs');
        }
    }
}
