package com.queueflow.api.service;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.exception.ResourceNotFoundException;
import com.queueflow.api.repository.AnalyticsRepository;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.BusinessRepository;
import com.queueflow.api.response.BranchAnalyticsResponse;
import com.queueflow.api.response.DailyTrendResponse;
import com.queueflow.api.response.ServiceAnalyticsResponse;
import com.queueflow.api.response.PeakHourResponse;
import com.queueflow.api.response.BusinessBranchAnalyticsResponse;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDate;
import java.time.temporal.ChronoUnit;
import java.util.List;

@Service
public class AnalyticsService {

    private final AnalyticsRepository analyticsRepository;
    private final BusinessRepository businessRepository;
    private final BranchRepository branchRepository;
    private final BusinessAuthorizationService authorizationService;

    public AnalyticsService(
            AnalyticsRepository analyticsRepository,
            BusinessRepository businessRepository,
            BranchRepository branchRepository,
            BusinessAuthorizationService authorizationService
    ) {
        this.analyticsRepository = analyticsRepository;
        this.businessRepository = businessRepository;
        this.branchRepository = branchRepository;
        this.authorizationService = authorizationService;
    }

    @Transactional(readOnly = true)
    public BranchAnalyticsResponse getBranchAnalytics(
            Long businessId,
            Long branchId,
            Long staffUserId,
            LocalDate from,
            LocalDate to
    ) {
        validateBranchAnalyticsAccess(
                businessId, branchId, staffUserId, from, to
        );

        List<Object[]> results = analyticsRepository.getBranchAnalytics(
                branchId,
                from,
                to
        );

        Object[] row = results.get(0);

        long total = asLong(row[0]);
        long completed = asLong(row[1]);
        long cancelled = asLong(row[2]);
        long skipped = asLong(row[3]);
        long waiting = asLong(row[4]);
        long called = asLong(row[5]);
        long serving = asLong(row[6]);

        double completionRate = total == 0
                ? 0.0
                : completed * 100.0 / total;

        double cancellationRate = total == 0
                ? 0.0
                : cancelled * 100.0 / total;

        return new BranchAnalyticsResponse(
                businessId,
                branchId,
                from,
                to,
                total,
                completed,
                cancelled,
                skipped,
                waiting,
                called,
                serving,
                completionRate,
                cancellationRate,
                asNullableDouble(row[7]),
                asNullableDouble(row[8])
        );
    }

    @Transactional(readOnly = true)
    public List<DailyTrendResponse> getDailyTrends(
            Long businessId,
            Long branchId,
            Long staffUserId,
            LocalDate from,
            LocalDate to
    ) {
        validateBranchAnalyticsAccess(
                businessId, branchId, staffUserId, from, to
        );

        return analyticsRepository.getDailyTrends(branchId, from, to)
                .stream()
                .map(row -> new DailyTrendResponse(
                        (LocalDate) row[0],
                        asLong(row[1]),
                        asLong(row[2]),
                        asLong(row[3]),
                        asLong(row[4])
                ))
                .toList();
    }
    @Transactional(readOnly = true)
    public List<ServiceAnalyticsResponse> getServiceAnalytics(
            Long businessId,
            Long branchId,
            Long staffUserId,
            LocalDate from,
            LocalDate to
    ) {
        validateBranchAnalyticsAccess(
                businessId, branchId, staffUserId, from, to
        );

        return analyticsRepository.getServiceAnalytics(branchId, from, to)
                .stream()
                .map(row -> new ServiceAnalyticsResponse(
                        asLong(row[0]),
                        asLong(row[1]),
                        asLong(row[2]),
                        asLong(row[3]),
                        asLong(row[4]),
                        asNullableDouble(row[5]),
                        asNullableDouble(row[6])
                ))
                .toList();
    }

    @Transactional(readOnly = true)
    public List<PeakHourResponse> getPeakHours(
            Long businessId,
            Long branchId,
            Long staffUserId,
            LocalDate from,
            LocalDate to
    ) {
        validateBranchAnalyticsAccess(
                businessId, branchId, staffUserId, from, to
        );

        return analyticsRepository.getPeakHours(branchId, from, to)
                .stream()
                .map(row -> new PeakHourResponse(
                        ((Number) row[0]).intValue(),
                        asLong(row[1])
                ))
                .toList();
    }

    @Transactional(readOnly = true)
    public List<BusinessBranchAnalyticsResponse> getBusinessBranchAnalytics(
            Long businessId,
            Long staffUserId,
            LocalDate from,
            LocalDate to
    ) {
        validateDateRange(from, to);

        if (!businessRepository.existsById(businessId)) {
            throw new ResourceNotFoundException(
                    "Business not found with id: " + businessId
            );
        }

        authorizationService.requireBusinessOwner(staffUserId, businessId);

        return analyticsRepository
                .getBusinessBranchAnalytics(businessId, from, to)
                .stream()
                .map(row -> new BusinessBranchAnalyticsResponse(
                        asLong(row[0]),
                        asLong(row[1]),
                        asLong(row[2]),
                        asLong(row[3]),
                        asLong(row[4])
                ))
                .toList();
    }
    private void validateBranchAnalyticsAccess(
            Long businessId,
            Long branchId,
            Long staffUserId,
            LocalDate from,
            LocalDate to
    ) {
        validateDateRange(from, to);

        if (!businessRepository.existsById(businessId)) {
            throw new ResourceNotFoundException(
                    "Business not found with id: " + businessId
            );
        }

        Branch branch = branchRepository.findById(branchId)
                .orElseThrow(() -> new ResourceNotFoundException(
                        "Branch not found with id: " + branchId
                ));

        if (!branch.getBusiness().getId().equals(businessId)) {
            throw new ResourceNotFoundException(
                    "Branch not found for this business"
            );
        }

        authorizationService.requireBranchManagementAccess(
                staffUserId, businessId, branchId
        );
    }
    private void validateDateRange(LocalDate from, LocalDate to) {
        if (from == null || to == null) {
            throw new IllegalArgumentException(
                    "Both from and to dates are required"
            );
        }

        if (from.isAfter(to)) {
            throw new IllegalArgumentException(
                    "From date cannot be after to date"
            );
        }

        if (ChronoUnit.DAYS.between(from, to) > 366) {
            throw new IllegalArgumentException(
                    "Analytics date range cannot exceed 366 days"
            );
        }
    }

    private long asLong(Object value) {
        return ((Number) value).longValue();
    }

    private Double asNullableDouble(Object value) {
        return value == null
                ? null
                : ((Number) value).doubleValue();
    }
}
