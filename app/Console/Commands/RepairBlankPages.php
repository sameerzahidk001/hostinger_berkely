<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairBlankPages extends Command
{
    protected $signature = 'pages:repair-blank
                            {--template= : Page ID to copy sections from}
                            {--auto : Pick template with most sections automatically}
                            {--dry-run : Show what would be fixed without writing}';

    protected $description = 'Copy page sections (incl. banner) to all category CMS pages that have zero sections';

    public function handle(): int
    {
        $templateId = $this->option('template');

        if ($this->option('auto') || ! $templateId) {
            $templateId = $this->resolveAutoTemplate();
        }

        if (! $templateId) {
            $this->error('No template page found. Pass --template=PAGE_ID or ensure one category page has sections.');

            return self::FAILURE;
        }

        $template = Page::withCount('sections')->find($templateId);

        if (! $template || $template->sections_count === 0) {
            $this->error("Template page {$templateId} has no sections.");

            return self::FAILURE;
        }

        $templateSections = PageSection::where('page_id', $templateId)
            ->orderBy('order')
            ->get();

        $emptyPages = Page::query()
            ->whereNotNull('category_id')
            ->where('id', '<>', $templateId)
            ->whereDoesntHave('sections')
            ->orderBy('id')
            ->get();

        if ($emptyPages->isEmpty()) {
            $this->info('No blank category pages found. Nothing to do.');

            return self::SUCCESS;
        }

        $this->info("Template: [{$templateId}] {$template->page_name} ({$template->sections_count} sections)");
        $this->info('Blank pages to fix: ' . $emptyPages->count());

        if ($this->option('dry-run')) {
            $emptyPages->each(fn (Page $p) => $this->line("  - [{$p->id}] {$p->page_name}"));

            return self::SUCCESS;
        }

        $fixed = 0;

        DB::transaction(function () use ($emptyPages, $templateSections, &$fixed) {
            foreach ($emptyPages as $page) {
                foreach ($templateSections as $section) {
                    $data = $section->data;
                    if (is_string($data)) {
                        $decoded = json_decode($data, true) ?? [];
                    } else {
                        $decoded = is_array($data) ? $data : [];
                    }

                    if (empty($decoded['section_type'])) {
                        $decoded['section_type'] = $section->section_type;
                    }

                    PageSection::create([
                        'page_id' => $page->id,
                        'section_type' => $section->section_type,
                        'order' => $section->order,
                        'data' => json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    ]);
                }

                $fixed++;
                $this->line("Fixed [{$page->id}] {$page->page_name}");
            }
        });

        $this->info("Done. Repaired {$fixed} pages.");

        return self::SUCCESS;
    }

    private function resolveAutoTemplate(): ?int
    {
        $row = DB::table('page_sections')
            ->join('pages', 'pages.id', '=', 'page_sections.page_id')
            ->whereNotNull('pages.category_id')
            ->select('page_sections.page_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('page_sections.page_id')
            ->orderByDesc('cnt')
            ->first();

        return $row?->page_id ? (int) $row->page_id : null;
    }
}
