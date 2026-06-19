<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::with(['createdBy', 'updatedBy'])->orderBy('title', 'asc')->get();
        return view('admin.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('admin.clients.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif',
            'url' => 'required|url',
        ]);        

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $client = new Client();

        if ($request->hasFile('image')) {
            $uploadedFile = $request->file('image');
            $filename = "client_" . time() . '.' . $uploadedFile->getClientOriginalExtension();
            $uploadedFile->move(public_path('images/clients'), $filename);

            $client->image = $filename;
        }

        $client->title = $request->input('title');
        $client->url = $request->url;
        $client->open_new_tab = $request->open_new_tab;
        $client->no_follow = $request->no_follow;
        $client->description = $request->input('description');
        assign_column_if_exists($client, 'image_alt', $request->input('image_alt'));
        $client->active = 1;
        $client->save();

        return redirect()->route('admin.clients.index')->with('success', 'Client section created successfully.');
    }

    public function edit($id)
    {
        $client = Client::findOrFail($id);
        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $client = Client::findOrFail($id);

        if ($request->hasFile('image')) {
            $uploadedFile = $request->file('image');
            $filename = "client_" . time() . '.' . $uploadedFile->getClientOriginalExtension();
            $uploadedFile->move(public_path('images/clients'), $filename);

            if (!empty($client->image) && file_exists(public_path('images/clients/' . $client->image))) {
                unlink(public_path('images/clients/' . $client->image));
            }

            $client->image = $filename;
        }

        // Update the database
        $client->title = $request->input('title');
        $client->url = $request->url;
        $client->open_new_tab = $request->open_new_tab;
        $client->no_follow = $request->no_follow;
        $client->description = $request->input('description');
        assign_column_if_exists($client, 'image_alt', $request->input('image_alt'));
        $client->active = $request->active;
        $client->save();

        return redirect()->route('admin.clients.index')->with('success', 'Client section updated successfully.');
    }


    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();
        return redirect()->route('admin.clients.index')->with('success', 'Client record deleted successfully.');
    }
}