<?php

namespace App\Models;

/**
 * Cohort Model
 *
 * Represents a cohort entity with all management fields.
 * Models are simple data containers — no database logic here.
 *
 * Matches the new `cohorts` table schema:
 *   id, cohort_code, name, correlative_number,
 *   total_admission_target, b2b_admission_target,
 *   b2b_admissions, b2c_admissions,
 *   admission_deadline_date, start_date, end_date,
 *   related_project, assigned_coach, bootcamp_type, area,
 *   assigned_class_schedule, training_status,
 *   created_at, updated_at
 */
class Cohort
{
    public const TRAINING_STATUS_NOT_STARTED = 'not_started';
    public const TRAINING_STATUS_IN_PROGRESS = 'in_progress';
    public const TRAINING_STATUS_COMPLETED   = 'completed';
    public const TRAINING_STATUS_CANCELLED   = 'cancelled';

    public const AREA_ACADEMIC   = 'academic';
    public const AREA_MARKETING  = 'marketing';
    public const AREA_ADMISSIONS = 'admissions';

    public function __construct(
        public readonly ?int    $id = null,
        public string           $cohortCode = '',
        public string           $name = '',
        public int              $correlativeNumber = 0,
        public int              $totalAdmissionTarget = 0,
        public int              $b2bAdmissionTarget = 0,
        public int              $b2bAdmissions = 0,
        public int              $b2cAdmissions = 0,
        public ?string          $admissionDeadlineDate = null,
        public ?string          $startDate = null,
        public ?string          $endDate = null,
        public ?string          $relatedProject = null,
        public ?string          $assignedCoach = null,
        public ?string          $bootcampType = null,
        public ?string          $area = null,
        public ?string          $assignedClassSchedule = null,
        public string           $trainingStatus = self::TRAINING_STATUS_NOT_STARTED,
        public ?string          $createdAt = null,
        public ?string          $updatedAt = null,
        // ─── Calculated fields (NOT persisted) ──────────────
        public ?string          $trainingDate50 = null,
        public ?string          $trainingDate75 = null,
    ) {}

    /**
     * Create a Cohort instance from a database row.
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id:                    isset($row['id']) ? (int) $row['id'] : null,
            cohortCode:            (string) ($row['cohort_code'] ?? ''),
            name:                  (string) ($row['name'] ?? ''),
            correlativeNumber:     (int) ($row['correlative_number'] ?? 0),
            totalAdmissionTarget:  (int) ($row['total_admission_target'] ?? 0),
            b2bAdmissionTarget:    (int) ($row['b2b_admission_target'] ?? 0),
            b2bAdmissions:         (int) ($row['b2b_admissions'] ?? 0),
            b2cAdmissions:         (int) ($row['b2c_admissions'] ?? 0),
            admissionDeadlineDate: $row['admission_deadline_date'] ?? null,
            startDate:             $row['start_date'] ?? null,
            endDate:               $row['end_date'] ?? null,
            relatedProject:        $row['related_project'] ?? null,
            assignedCoach:         $row['assigned_coach'] ?? null,
            bootcampType:          $row['bootcamp_type'] ?? null,
            area:                  $row['area'] ?? null,
            assignedClassSchedule: $row['assigned_class_schedule'] ?? null,
            trainingStatus:        (string) ($row['training_status'] ?? self::TRAINING_STATUS_NOT_STARTED),
            createdAt:             $row['created_at'] ?? null,
            updatedAt:             $row['updated_at'] ?? null,
            trainingDate50:        $row['training_date_50'] ?? null,
            trainingDate75:        $row['training_date_75'] ?? null,
        );
    }

    /**
     * Convert the model to an associative array using the database column names.
     */
    public function toArray(): array
    {
        return [
            'id'                       => $this->id,
            'cohort_code'              => $this->cohortCode,
            'name'                     => $this->name,
            'correlative_number'       => $this->correlativeNumber,
            'total_admission_target'   => $this->totalAdmissionTarget,
            'b2b_admission_target'     => $this->b2bAdmissionTarget,
            'b2b_admissions'           => $this->b2bAdmissions,
            'b2c_admissions'           => $this->b2cAdmissions,
            'admission_deadline_date'  => $this->admissionDeadlineDate,
            'start_date'               => $this->startDate,
            'end_date'                 => $this->endDate,
            'related_project'          => $this->relatedProject,
            'assigned_coach'           => $this->assignedCoach,
            'bootcamp_type'            => $this->bootcampType,
            'area'                     => $this->area,
            'assigned_class_schedule'  => $this->assignedClassSchedule,
            'training_status'          => $this->trainingStatus,
            'created_at'               => $this->createdAt,
            'updated_at'               => $this->updatedAt,
            'training_date_50'         => $this->trainingDate50,
            'training_date_75'         => $this->trainingDate75,
        ];
    }

    /**
     * Check if the cohort training is currently in progress.
     */
    public function isInProgress(): bool
    {
        return $this->trainingStatus === self::TRAINING_STATUS_IN_PROGRESS;
    }

    /**
     * Check if the cohort training is completed.
     */
    public function isCompleted(): bool
    {
        return $this->trainingStatus === self::TRAINING_STATUS_COMPLETED;
    }

    /**
     * Check if the cohort training has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->trainingStatus === self::TRAINING_STATUS_CANCELLED;
    }

    /**
     * Check if the cohort has not yet started.
     */
    public function isNotStarted(): bool
    {
        return $this->trainingStatus === self::TRAINING_STATUS_NOT_STARTED;
    }
}
