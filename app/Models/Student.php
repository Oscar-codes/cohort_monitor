<?php

namespace App\Models;

/**
 * Student Model
 *
 * Represents a student entity.
 * Prepared for future implementation.
 */
class Student
{
    public function __construct(
        public readonly ?int    $id = null,
        public string           $firstName = '',
        public string           $lastName = '',
        public ?string          $email = null,
        public ?int             $cohortId = null,
        public string           $status = 'active',
        public ?string          $createdAt = null,
        public ?string          $updatedAt = null,
    ) {}

    /**
     * Create a Student instance from a database row.
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id:        (int) ($row['id'] ?? 0),
            firstName: $row['first_name'] ?? '',
            lastName:  $row['last_name'] ?? '',
            email:     $row['email'] ?? null,
            cohortId:  isset($row['cohort_id']) ? (int) $row['cohort_id'] : null,
            status:    $row['status'] ?? 'active',
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
        );
    }

    /**
     * Get the student's full name.
     */
    public function fullName(): string
    {
        return trim("{$this->firstName} {$this->lastName}");
    }

    /**
     * Convert the model to an associative array.
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'first_name' => $this->firstName,
            'last_name'  => $this->lastName,
            'email'      => $this->email,
            'cohort_id'  => $this->cohortId,
            'status'     => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
