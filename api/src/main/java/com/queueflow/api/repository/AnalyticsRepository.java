package com.queueflow.api.repository;

import com.queueflow.api.entity.QueueEntry;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.data.repository.query.Param;

import java.time.LocalDate;
import java.util.List;

public interface AnalyticsRepository
        extends JpaRepository<QueueEntry, Long> {

    @Query(value = """
            SELECT
                COUNT(*) AS total_entries,
                COUNT(*) FILTER (WHERE e.status = 'COMPLETED') AS completed_entries,
                COUNT(*) FILTER (WHERE e.status = 'CANCELLED') AS cancelled_entries,
                COUNT(*) FILTER (WHERE e.status = 'SKIPPED') AS skipped_entries,
                COUNT(*) FILTER (WHERE e.status = 'WAITING') AS waiting_entries,
                COUNT(*) FILTER (WHERE e.status = 'CALLED') AS called_entries,
                COUNT(*) FILTER (WHERE e.status = 'SERVING') AS serving_entries,
                AVG(EXTRACT(EPOCH FROM (e.serving_at - e.joined_at)) / 60.0)
                    FILTER (
                        WHERE e.serving_at IS NOT NULL
                        AND e.joined_at IS NOT NULL
                        AND e.serving_at >= e.joined_at
                    ) AS average_wait_minutes,
                AVG(EXTRACT(EPOCH FROM (e.completed_at - e.serving_at)) / 60.0)
                    FILTER (
                        WHERE e.completed_at IS NOT NULL
                        AND e.serving_at IS NOT NULL
                        AND e.completed_at >= e.serving_at
                    ) AS average_service_minutes
            FROM queue_entry e
            JOIN queue q ON q.id = e.queue_id
            WHERE q.branch_id = :branchId
              AND q.business_date BETWEEN :fromDate AND :toDate
            """, nativeQuery = true)
    List<Object[]> getBranchAnalytics(
            @Param("branchId") Long branchId,
            @Param("fromDate") LocalDate fromDate,
            @Param("toDate") LocalDate toDate
    );

    @Query(value = """
            SELECT
                q.business_date AS report_date,
                COUNT(*) AS total_entries,
                COUNT(*) FILTER (
                    WHERE e.status = 'COMPLETED'
                ) AS completed_entries,
                COUNT(*) FILTER (
                    WHERE e.status = 'CANCELLED'
                ) AS cancelled_entries,
                COUNT(*) FILTER (
                    WHERE e.status = 'SKIPPED'
                ) AS skipped_entries
            FROM queue_entry e
            JOIN queue q ON q.id = e.queue_id
            WHERE q.branch_id = :branchId
              AND q.business_date BETWEEN :fromDate AND :toDate
            GROUP BY q.business_date
            ORDER BY q.business_date ASC
            """, nativeQuery = true)
    List<Object[]> getDailyTrends(
            @Param("branchId") Long branchId,
            @Param("fromDate") LocalDate fromDate,
            @Param("toDate") LocalDate toDate
    );

    @Query(value = """
            SELECT
                e.service_id,
                COUNT(*),
                COUNT(*) FILTER (WHERE e.status = 'COMPLETED'),
                COUNT(*) FILTER (WHERE e.status = 'CANCELLED'),
                COUNT(*) FILTER (WHERE e.status = 'SKIPPED'),
                AVG(EXTRACT(EPOCH FROM (e.serving_at - e.joined_at)) / 60.0)
                    FILTER (WHERE e.serving_at >= e.joined_at),
                AVG(EXTRACT(EPOCH FROM (e.completed_at - e.serving_at)) / 60.0)
                    FILTER (WHERE e.completed_at >= e.serving_at)
            FROM queue_entry e
            JOIN queue q ON q.id = e.queue_id
            WHERE q.branch_id = :branchId
              AND q.business_date BETWEEN :fromDate AND :toDate
            GROUP BY e.service_id
            ORDER BY e.service_id
            """, nativeQuery = true)
    List<Object[]> getServiceAnalytics(
            @Param("branchId") Long branchId,
            @Param("fromDate") LocalDate fromDate,
            @Param("toDate") LocalDate toDate
    );

    @Query(value = """
            SELECT
                EXTRACT(HOUR FROM e.joined_at AT TIME ZONE b.timezone)::integer,
                COUNT(*)
            FROM queue_entry e
            JOIN queue q ON q.id = e.queue_id
            JOIN branch b ON b.id = q.branch_id
            WHERE q.branch_id = :branchId
              AND q.business_date BETWEEN :fromDate AND :toDate
            GROUP BY 1
            ORDER BY 1
            """, nativeQuery = true)
    List<Object[]> getPeakHours(
            @Param("branchId") Long branchId,
            @Param("fromDate") LocalDate fromDate,
            @Param("toDate") LocalDate toDate
    );

    @Query(value = """
            SELECT
                q.branch_id,
                COUNT(*),
                COUNT(*) FILTER (WHERE e.status = 'COMPLETED'),
                COUNT(*) FILTER (WHERE e.status = 'CANCELLED'),
                COUNT(*) FILTER (WHERE e.status = 'SKIPPED')
            FROM queue_entry e
            JOIN queue q ON q.id = e.queue_id
            JOIN branch b ON b.id = q.branch_id
            WHERE b.business_id = :businessId
              AND q.business_date BETWEEN :fromDate AND :toDate
            GROUP BY q.branch_id
            ORDER BY q.branch_id
            """, nativeQuery = true)
    List<Object[]> getBusinessBranchAnalytics(
            @Param("businessId") Long businessId,
            @Param("fromDate") LocalDate fromDate,
            @Param("toDate") LocalDate toDate
    );
}
