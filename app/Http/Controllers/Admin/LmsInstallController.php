<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class LmsInstallController extends Controller
{
    public function show()
    {
        if ($this->tablesReady()) {
            return redirect()
                ->route('admin.study-materials.folders.index')
                ->with('success', 'LMS is already installed.');
        }

        return view('admin.lms.install', [
            'ready' => false,
            'tables' => $this->tableStatus(),
        ]);
    }

    public function run(Request $request)
    {
        if ($this->tablesReady()) {
            return redirect()
                ->route('admin.study-materials.folders.index')
                ->with('success', 'LMS tables already exist.');
        }

        $exit = Artisan::call('lms:install');
        $output = trim(Artisan::output());

        if ($exit !== 0 || ! $this->tablesReady()) {
            return redirect()
                ->route('admin.lms.install')
                ->with('fail', 'LMS install failed. Do not run full migrate. Output: ' . $output);
        }

        return redirect()
            ->route('admin.study-materials.folders.index')
            ->with('success', 'LMS tables created. Study Materials is ready.');
    }

    protected function tablesReady(): bool
    {
        return Schema::hasTable('study_material_folders')
            && Schema::hasTable('study_material_items')
            && Schema::hasTable('study_material_student_access')
            && Schema::hasTable('study_material_instructor_access')
            && Schema::hasTable('class_schedules');
    }

    protected function tableStatus(): array
    {
        $names = [
            'study_material_folders',
            'study_material_items',
            'study_material_folder_packages',
            'study_material_student_access',
            'study_material_instructor_access',
            'class_schedules',
            'class_schedule_student',
        ];

        $status = [];
        foreach ($names as $name) {
            $status[$name] = Schema::hasTable($name);
        }

        return $status;
    }
}
