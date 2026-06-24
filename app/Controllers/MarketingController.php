<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\MarketingService;
use App\Services\CohortService;

/**
 * MarketingController — Marketing workflow stages per cohort.
 */
class MarketingController extends Controller
{
    private MarketingService $mktService;
    private CohortService    $cohortService;

    public function __construct()
    {
        Auth::requireRole(['admin', 'marketing']);
        $this->mktService    = new MarketingService();
        $this->cohortService = new CohortService();
    }

    /** Show marketing stages for a cohort. */
    public function show(string $cohortId): void
    {
        $cohort = $this->cohortService->getCohortById((int) $cohortId);
        if (!$cohort) {
            http_response_code(404);
            $this->view('errors.404', ['pageTitle' => 'No Encontrado'], null);
            return;
        }

        $stages = $this->mktService->getStagesForCohort((int) $cohortId);
        $marketingInfo = $this->mktService->getMarketingInfo((int) $cohortId);

        $this->view('marketing.show', [
            'pageTitle'   => 'Marketing — ' . $cohort['name'],
            'activePage'  => 'marketing',
            'cohort'      => $cohort,
            'stages'      => $stages,
            'marketingInfo' => $marketingInfo,
            'stageLabels' => MarketingService::STAGE_LABELS,
            'statusLabels'=> MarketingService::STATUS_LABELS,
            'scripts'     => [
                '/assets/js/marketing-show.js',
            ],
        ]);
    }

    /** Update stage status (POST). */
    public function update(string $cohortId): void
    {
        $stageName = $this->input('stage_name');
        $status    = $this->input('status');
        $riskNotes = $this->input('risk_notes');

        try {
            $this->mktService->updateStage((int) $cohortId, $stageName, $status, $riskNotes);
            Auth::flash('success', 'Etapa actualizada correctamente.');
        } catch (\InvalidArgumentException $e) {
            Auth::flash('error', $e->getMessage());
        }

        $this->redirect('/cohorts/' . $cohortId . '/marketing');
    }

    /** Update marketing info (POST). */
    public function updateInfo(string $cohortId): void
    {
        $data = [
            'campaign_status'    => $this->input('campaign_status'),
            'strategy_notes'     => $this->input('strategy_notes'),
            'content_notes'      => $this->input('content_notes'),
            'ads_notes'          => $this->input('ads_notes'),
            'organic_notes'      => $this->input('organic_notes'),
            'events_notes'       => $this->input('events_notes'),
            'partnerships_notes' => $this->input('partnerships_notes'),
            'analytics_notes'    => $this->input('analytics_notes'),
        ];

        try {
            $this->mktService->updateMarketingInfo((int) $cohortId, $data);
            Auth::flash('success', 'Información de marketing actualizada correctamente.');
        } catch (\InvalidArgumentException $e) {
            Auth::flash('error', $e->getMessage());
        }

        $this->redirect('/cohorts/' . $cohortId . '/marketing');
    }

    /** List cohorts for marketing. */
    public function index(): void
    {
        $filters = [
            'search'          => (string) $this->input('search', ''),
            'bootcamp_type'   => (string) $this->input('bootcamp_type', ''),
            'related_project' => (string) $this->input('related_project', ''),
            'start_date'      => (string) $this->input('start_date', ''),
            'end_date'        => (string) $this->input('end_date', ''),
            'business_model'  => (string) $this->input('business_model', ''),
            'cohort_status'   => (string) $this->input('cohort_status', ''),
        ];

        $cohorts = $this->cohortService->getFilteredCohorts($filters);
        $bootcampTypes = $this->cohortService->getBootcampTypes();
        $projectNames = $this->cohortService->getProjectNames();

        $totalCohorts = count($cohorts);
        $campaignsActive = 0;
        $campaignsCompleted = 0;
        $stagesOnTrack = 0;
        $stagesTotal = 0;

        // Enrich cohorts with marketing info and stages data
        foreach ($cohorts as &$c) {
            $c['marketing_stages'] = $this->mktService->getStagesForCohort((int) $c['id']);
            $c['marketing_info'] = $this->mktService->getMarketingInfo((int) $c['id']);

            $stagesTotal += max(1, count($c['marketing_stages']));
            foreach ($c['marketing_stages'] as $st) {
                if (($st['status'] ?? '') === 'completed') {
                    $stagesOnTrack++;
                }
            }
            if ($c['marketing_info'] && ($c['marketing_info']['campaign_status'] ?? 'Active') === 'Active') {
                $campaignsActive++;
            } else {
                $campaignsCompleted++;
            }
        }
        unset($c);

        $stagesPct = $stagesTotal > 0 ? min(100, (int) round(($stagesOnTrack / $stagesTotal) * 100)) : 0;

        $this->view('marketing.index', [
            'pageTitle'         => 'Marketing Cohort Plan',
            'activePage'        => 'marketing',
            'cohorts'           => $cohorts,
            'filters'           => $filters,
            'activeFilters'     => array_filter($filters, static fn($v) => $v !== ''),
            'bootcampTypes'     => $bootcampTypes,
            'projectNames'      => $projectNames,
            'totalCohorts'      => $totalCohorts,
            'campaignsActive'   => $campaignsActive,
            'campaignsCompleted'=> $campaignsCompleted,
            'stagesOnTrack'     => $stagesOnTrack,
            'stagesTotal'       => $stagesTotal,
            'stagesPct'         => $stagesPct,
        ]);
    }
}
