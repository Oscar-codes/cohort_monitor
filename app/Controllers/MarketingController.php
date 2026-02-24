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

        $this->view('marketing.show', [
            'pageTitle'   => 'Marketing — ' . $cohort['name'],
            'activePage'  => 'marketing',
            'cohort'      => $cohort,
            'stages'      => $stages,
            'stageLabels' => MarketingService::STAGE_LABELS,
            'statusLabels'=> MarketingService::STATUS_LABELS,
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

    /** List cohorts for marketing (selector). */
    public function index(): void
    {
        $cohorts = $this->cohortService->getAllCohorts();

        $this->view('marketing.index', [
            'pageTitle'  => 'Marketing — Seleccionar Cohorte',
            'activePage' => 'marketing',
            'cohorts'    => $cohorts,
        ]);
    }
}
