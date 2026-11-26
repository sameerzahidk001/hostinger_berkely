<?php

namespace App\Http\Controllers\Admin\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalManuscript;
use App\Services\JournalSetupService;
use Illuminate\Http\Request;

class ManuscriptAdminController extends Controller
{
    public function __construct()
    {
        app(JournalSetupService::class)->ensureAll();
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $query = JournalManuscript::query()->with(['author', 'subject', 'editor'])->orderByDesc('id');
        if ($status) {
            $query->where('status', $status);
        }
        $rows = $query->paginate(40)->withQueryString();

        return view('admin.journal.manuscripts.index', compact('rows', 'status'));
    }
}
