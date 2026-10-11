package com.queueflow.api.repository;

import com.queueflow.api.entity.Business;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

public interface BusinessRepository extends JpaRepository<Business, Long> {

    Optional<Business> findByPublicCode(String publicCode);
}