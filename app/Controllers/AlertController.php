<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\AlertService;

/**
 * AlertController — Alerts / risk dashboard (all authenticated roles).
 */
class AlertController extends Controller
{
    private AlertService $alertService;

    public function __construct()
    {
        Auth::requireLogin();
        $this->alertService = new AlertService();
    }

    /** Main alerts dashboard. */
    public function index(): void
    {
        $data = $this->alertService->getAlertsSummary();

        $this->view('alerts.index', [
            'pageTitle'      => 'Alertas y Riesgos',
            'activePage'     => 'alerts',
            'riskComments'   => $data['risk_comments'],
            'atRiskStages'   => $data['at_risk_stages'],
            'risksByCohort'  => $data['risks_by_cohort'],
        ]);
    }
}
