<?php

namespace App\Models;

/**
 * Cohort Model
 *
 * Represents a cohort entity with all management fields.
 * Models are simple data containers — no database logic here.
 *
 * NOTE: training_date_50 and training_date_75 are calculated fields
 *       computed in the Service layer, never stored in the database.
 */
class Cohort
{
    public function __construct(
        public readonly ?int    $id = null,
        public string           $cohortCode = '',
        public string           $name = '',
        public int              $correlativeNumber = 0,
        public int              $totalAdmissionTarget = 0,
        public int              $b2bAdmissionTarget = 0,
        public int              $b2cAdmissions = 0,
        public ?string          $admissionDeadlineDate = null,
        public ?string          $startDate = null,
        public ?string          $endDate = null,
        public ?string          $relatedProject = null,
        public ?string          $assignedCoach = null,
        public ?string          $bootcampType = null,
        public ?string          $assignedClassSchedule = null,
        public string           $trainingStatus = 'not_started',
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
            id:                    (int) ($row['id'] ?? 0),
            cohortCode:            $row['cohort_code'] ?? '',
            name:                  $row['name'] ?? '',
            correlativeNumber:     (int) ($row['correlative_number'] ?? 0),
            totalAdmissionTarget:  (int) ($row['total_admission_target'] ?? 0),
            b2bAdmissionTarget:    (int) ($row['b2b_admission_target'] ?? 0),
            b2cAdmissions:         (int) ($row['b2c_admissions'] ?? 0),
            admissionDeadlineDate: $row['admission_deadline_date'] ?? null,
            startDate:             $row['start_date'] ?? null,
            endDate:               $row['end_date'] ?? null,
            relatedProject:        $row['related_project'] ?? null,
            assignedCoach:         $row['assigned_coach'] ?? null,
            bootcampType:          $row['bootcamp_type'] ?? null,
            assignedClassSchedule: $row['assigned_class_schedule'] ?? null,
            trainingStatus:        $row['training_status'] ?? 'not_started',
            createdAt:             $row['created_at'] ?? null,
            updatedAt:             $row['updated_at'] ?? null,
            trainingDate50:        $row['training_date_50'] ?? null,
            trainingDate75:        $row['training_date_75'] ?? null,
        );
    }

    /**
     * Convert the model to an associative array.
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
            'b2c_admissions'           => $this->b2cAdmissions,
            'admission_deadline_date'  => $this->admissionDeadlineDate,
            'start_date'               => $this->startDate,
            'end_date'                 => $this->endDate,
            'related_project'          => $this->relatedProject,
            'assigned_coach'           => $this->assignedCoach,
            'bootcamp_type'            => $this->bootcampType,
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
        return $this->trainingStatus === 'in_progress';
    }

    /**
     * Check if the cohort training is completed.
     */
    public function isCompleted(): bool
    {
        return $this->trainingStatus === 'completed';
    }
}
