<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\SystemSetting;
use App\Services\ElectionResultService;

class PublicResultController extends Controller
{
    public function __invoke(Election $election, ElectionResultService $resultService)
    {
        $globalPublicResultsEnabled = SystemSetting::query()
            ->where('key', 'public_results_enabled')
            ->value('value') === '1';

        abort_unless(
            $globalPublicResultsEnabled
                && $election->status === 'published'
                && $election->results_visible_to_public,
            404
        );

        return view('public.results.show', $resultService->payload($election));
    }
}
