<?php

namespace App\Repositories;

use App\Core\Database;

/**
 * CohortMarketingInfoRepository - Data access for cohort_marketing_info table
 */
class CohortMarketingInfoRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get marketing info for a cohort
     */
    public function findByCohort(int $cohortId): ?array
    {
        $result = $this->db->query(
            'SELECT * FROM cohort_marketing_info WHERE cohort_id = :cohort_id LIMIT 1',
            ['cohort_id' => $cohortId]
        );
        
        return $result[0] ?? null;
    }

    /**
     * Insert or update marketing info for a cohort
     */
    public function upsert(int $cohortId, array $data): void
    {
        $sql = "INSERT INTO cohort_marketing_info 
                (cohort_id, campaign_status, strategy_notes, content_notes, ads_notes, 
                 organic_notes, events_notes, partnerships_notes, analytics_notes)
                VALUES (:cohort_id, :campaign_status, :strategy_notes, :content_notes, :ads_notes,
                        :organic_notes, :events_notes, :partnerships_notes, :analytics_notes)
                ON DUPLICATE KEY UPDATE
                    campaign_status = VALUES(campaign_status),
                    strategy_notes = VALUES(strategy_notes),
                    content_notes = VALUES(content_notes),
                    ads_notes = VALUES(ads_notes),
                    organic_notes = VALUES(organic_notes),
                    events_notes = VALUES(events_notes),
                    partnerships_notes = VALUES(partnerships_notes),
                    analytics_notes = VALUES(analytics_notes),
                    updated_at = NOW()";

        $this->db->execute($sql, array_merge(['cohort_id' => $cohortId], $data));
    }

    /**
     * Delete marketing info for a cohort
     */
    public function delete(int $cohortId): void
    {
        $this->db->execute(
            'DELETE FROM cohort_marketing_info WHERE cohort_id = :cohort_id',
            ['cohort_id' => $cohortId]
        );
    }
}
