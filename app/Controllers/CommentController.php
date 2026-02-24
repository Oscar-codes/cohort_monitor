<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\AlertService;
use App\Services\CohortService;

/**
 * CommentController — Cohort comments / risk notes (all roles).
 */
class CommentController extends Controller
{
    private AlertService  $alertService;
    private CohortService $cohortService;

    public function __construct()
    {
        Auth::requireLogin();
        $this->alertService  = new AlertService();
        $this->cohortService = new CohortService();
    }

    /** Store a new comment (AJAX-friendly or redirect). */
    public function store(string $cohortId): void
    {
        $body     = trim($this->input('body', ''));
        $category = $this->input('category', 'general');

        if (empty($body)) {
            Auth::flash('error', 'El comentario no puede estar vacío.');
            $this->redirect('/cohorts/' . $cohortId);
            return;
        }

        $validCategories = ['risk', 'general', 'admission', 'marketing'];
        if (!in_array($category, $validCategories, true)) {
            $category = 'general';
        }

        $this->alertService->addComment((int) $cohortId, $body, $category);
        Auth::flash('success', 'Comentario agregado.');
        $this->redirect('/cohorts/' . $cohortId);
    }
}
