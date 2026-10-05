package com.queueflow.api.service;

import com.queueflow.api.entity.Branch;
import com.queueflow.api.repository.BranchRepository;
import com.queueflow.api.repository.ServiceRepository;
import com.queueflow.api.response.PublicDiscoveryResponse;
import com.queueflow.api.response.PublicDiscoveryServiceResponse;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.Comparator;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.stream.Collectors;

@Service
public class PublicDiscoveryService {

    private final BranchRepository branchRepository;
    private final ServiceRepository serviceRepository;

    public PublicDiscoveryService(
            BranchRepository branchRepository,
            ServiceRepository serviceRepository
    ) {
        this.branchRepository = branchRepository;
        this.serviceRepository = serviceRepository;
    }

    @Transactional(readOnly = true)
    public List<PublicDiscoveryResponse> discover(
            String search,
            String category,
            Double latitude,
            Double longitude
    ) {

        validateCoordinates(
                latitude,
                longitude
        );

        String normalizedSearch =
                normalize(search);

        String normalizedCategory =
                normalize(category);

        List<Branch> branches =
                branchRepository.findAllByOrderByNameAsc();

        List<Long> branchIds =
                branches.stream()
                        .map(Branch::getId)
                        .toList();

        Map<Long, List<com.queueflow.api.entity.Service>>
                servicesByBranch =
                branchIds.isEmpty()
                        ? Map.of()
                        : serviceRepository
                        .findByBranchIdInAndActiveTrue(branchIds)
                        .stream()
                        .collect(
                                Collectors.groupingBy(
                                        service ->
                                                service.getBranch()
                                                        .getId()
                                )
                        );

        return branches.stream()
                .map(branch ->
                        toDiscoveryResponse(
                                branch,
                                servicesByBranch.getOrDefault(
                                        branch.getId(),
                                        List.of()
                                ),
                                latitude,
                                longitude
                        )
                )
                .filter(response ->
                        matchesCategory(
                                response,
                                normalizedCategory
                        )
                )
                .filter(response ->
                        matchesSearch(
                                response,
                                normalizedSearch
                        )
                )
                .sorted(
                        discoveryComparator(
                                latitude,
                                longitude
                        )
                )
                .toList();
    }

    private PublicDiscoveryResponse toDiscoveryResponse(
            Branch branch,
            List<com.queueflow.api.entity.Service> branchServices,
            Double latitude,
            Double longitude
    ) {

        List<PublicDiscoveryServiceResponse> services =
                branchServices
                        .stream()
                        .map(service ->
                                new PublicDiscoveryServiceResponse(
                                        service.getId(),
                                        service.getName(),
                                        service.getDescription(),
                                        service.getDurationMinutes()
                                )
                        )
                        .toList();

        Double distanceKm =
                calculateDistanceKm(
                        latitude,
                        longitude,
                        branch
                );

        return new PublicDiscoveryResponse(
                branch.getBusiness().getId(),
                branch.getBusiness().getName(),
                branch.getBusiness().getDescription(),
                branch.getBusiness().getCategory(),
                branch.getId(),
                branch.getName(),
                branch.getAddress(),
                branch.getLatitude(),
                branch.getLongitude(),
                distanceKm,
                services
        );
    }

    private boolean matchesCategory(
            PublicDiscoveryResponse response,
            String category
    ) {

        if (category == null) {
            return true;
        }

        return response.category() != null
                && response.category()
                .equalsIgnoreCase(category);
    }

    private boolean matchesSearch(
            PublicDiscoveryResponse response,
            String search
    ) {

        if (search == null) {
            return true;
        }

        if (contains(
                response.businessName(),
                search
        )) {
            return true;
        }

        if (contains(
                response.businessDescription(),
                search
        )) {
            return true;
        }

        if (contains(
                response.branchName(),
                search
        )) {
            return true;
        }

        if (contains(
                response.address(),
                search
        )) {
            return true;
        }

        return response.services()
                .stream()
                .anyMatch(service ->
                        contains(
                                service.name(),
                                search
                        )
                                || contains(
                                service.description(),
                                search
                        )
                );
    }

    private boolean contains(
            String value,
            String search
    ) {

        return value != null
                && value.toLowerCase(Locale.ROOT)
                .contains(search);
    }

    private String normalize(
            String value
    ) {

        if (value == null || value.isBlank()) {
            return null;
        }

        return value.trim()
                .toLowerCase(Locale.ROOT);
    }

    private void validateCoordinates(
            Double latitude,
            Double longitude
    ) {

        if ((latitude == null) != (longitude == null)) {
            throw new IllegalArgumentException(
                    "Latitude and longitude must be provided together"
            );
        }

        if (latitude == null) {
            return;
        }

        if (latitude < -90 || latitude > 90) {
            throw new IllegalArgumentException(
                    "Latitude must be between -90 and 90"
            );
        }

        if (longitude < -180 || longitude > 180) {
            throw new IllegalArgumentException(
                    "Longitude must be between -180 and 180"
            );
        }
    }

    private Double calculateDistanceKm(
            Double latitude,
            Double longitude,
            Branch branch
    ) {

        if (latitude == null
                || longitude == null
                || branch.getLatitude() == null
                || branch.getLongitude() == null) {

            return null;
        }

        double earthRadiusKm = 6371.0;

        double latitude1 =
                Math.toRadians(latitude);

        double latitude2 =
                Math.toRadians(
                        branch.getLatitude()
                                .doubleValue()
                );

        double latitudeDifference =
                Math.toRadians(
                        branch.getLatitude()
                                .doubleValue()
                                - latitude
                );

        double longitudeDifference =
                Math.toRadians(
                        branch.getLongitude()
                                .doubleValue()
                                - longitude
                );

        double a =
                Math.sin(latitudeDifference / 2)
                        * Math.sin(latitudeDifference / 2)
                        + Math.cos(latitude1)
                        * Math.cos(latitude2)
                        * Math.sin(longitudeDifference / 2)
                        * Math.sin(longitudeDifference / 2);

        double c =
                2 * Math.atan2(
                        Math.sqrt(a),
                        Math.sqrt(1 - a)
                );

        return earthRadiusKm * c;
    }

    private Comparator<PublicDiscoveryResponse>
    discoveryComparator(
            Double latitude,
            Double longitude
    ) {

        if (latitude != null && longitude != null) {
            return Comparator
                    .comparing(
                            PublicDiscoveryResponse::distanceKm,
                            Comparator.nullsLast(
                                    Comparator.naturalOrder()
                            )
                    )
                    .thenComparing(
                            PublicDiscoveryResponse::businessName,
                            String.CASE_INSENSITIVE_ORDER
                    )
                    .thenComparing(
                            PublicDiscoveryResponse::branchName,
                            String.CASE_INSENSITIVE_ORDER
                    );
        }

        return Comparator
                .comparing(
                        PublicDiscoveryResponse::businessName,
                        String.CASE_INSENSITIVE_ORDER
                )
                .thenComparing(
                        PublicDiscoveryResponse::branchName,
                        String.CASE_INSENSITIVE_ORDER
                );
    }
}