<?php

namespace App\Models;

/**
 * Cohort Model
 *
 * Represents a cohort entity.
 * Models are simple data containers — no database logic here.
 */
class Cohort
{
    public function __construct(
        public readonly ?int    $id = null,
        public string           $name = '',
        public ?string          $description = null,
        public ?string          $startDate = null,
        public ?string          $endDate = null,
        public string           $status = 'active',
        public ?string          $createdAt = null,
        public ?string          $updatedAt = null,
    ) {}

    /**
     * Create a Cohort instance from a database row.
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id:          (int) ($row['id'] ?? 0),
            name:        $row['name'] ?? '',
            description: $row['description'] ?? null,
            startDate:   $row['start_date'] ?? null,
            endDate:     $row['end_date'] ?? null,
            status:      $row['status'] ?? 'active',
            createdAt:   $row['created_at'] ?? null,
            updatedAt:   $row['updated_at'] ?? null,
        );
    }

    /**
     * Convert the model to an associative array.
     */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'start_date'  => $this->startDate,
            'end_date'    => $this->endDate,
            'status'      => $this->status,
            'created_at'  => $this->createdAt,
            'updated_at'  => $this->updatedAt,
        ];
    }

    /**
     * Check if the cohort is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
