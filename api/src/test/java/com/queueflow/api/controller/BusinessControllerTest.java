package com.queueflow.api.controller;

import com.queueflow.api.entity.Business;
import com.queueflow.api.repository.BusinessRepository;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.boot.webmvc.test.autoconfigure.AutoConfigureMockMvc;
import org.springframework.http.MediaType;
import org.springframework.test.web.servlet.MockMvc;

import static org.springframework.test.web.servlet.request.MockMvcRequestBuilders.*;
import static org.springframework.test.web.servlet.result.MockMvcResultMatchers.*;

@SpringBootTest
@AutoConfigureMockMvc
class BusinessControllerTest {

    @Autowired
    private MockMvc mockMvc;

    @Autowired
    private BusinessRepository businessRepository;

    @BeforeEach
    void cleanDatabase() {
        businessRepository.deleteAll();
    }

    @Test
    void shouldCreateBusiness() throws Exception {
        String requestBody = """
                {
                    "name": "QueueFlow Clinic",
                    "description": "Medical clinic"
                }
                """;

        mockMvc.perform(post("/api/v1/businesses")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content(requestBody))
                .andExpect(status().isCreated())
                .andExpect(content().contentTypeCompatibleWith(MediaType.APPLICATION_JSON))
                .andExpect(jsonPath("$.id").isNumber())
                .andExpect(jsonPath("$.name").value("QueueFlow Clinic"))
                .andExpect(jsonPath("$.description").value("Medical clinic"))
                .andExpect(jsonPath("$.createdAt").exists());

        org.assertj.core.api.Assertions
                .assertThat(businessRepository.count())
                .isEqualTo(1);
    }

    @Test
    void shouldRejectBlankBusinessName() throws Exception {
        String requestBody = """
                {
                    "name": "",
                    "description": "Medical clinic"
                }
                """;

        mockMvc.perform(post("/api/v1/businesses")
                        .contentType(MediaType.APPLICATION_JSON)
                        .content(requestBody))
                .andExpect(status().isBadRequest())
                .andExpect(content().contentTypeCompatibleWith(MediaType.APPLICATION_JSON))
                .andExpect(jsonPath("$.status").value(400))
                .andExpect(jsonPath("$.error").value("Bad Request"))
                .andExpect(jsonPath("$.message").value("Request validation failed"))
                .andExpect(jsonPath("$.path").value("/api/v1/businesses"))
                .andExpect(jsonPath("$.validationErrors.name")
                        .value("Business name is required"));
    }

    @Test
    void shouldGetBusinessById() throws Exception {
        Business business = businessRepository.save(
                new Business("QueueFlow Clinic", "Medical clinic")
        );

        mockMvc.perform(get("/api/v1/businesses/{id}", business.getId()))
                .andExpect(status().isOk())
                .andExpect(content().contentTypeCompatibleWith(MediaType.APPLICATION_JSON))
                .andExpect(jsonPath("$.id").value(business.getId()))
                .andExpect(jsonPath("$.name").value("QueueFlow Clinic"))
                .andExpect(jsonPath("$.description").value("Medical clinic"))
                .andExpect(jsonPath("$.createdAt").exists());
    }

    @Test
    void shouldReturnNotFoundWhenBusinessDoesNotExist() throws Exception {
        mockMvc.perform(get("/api/v1/businesses/{id}", 999999L))
                .andExpect(status().isNotFound())
                .andExpect(content().contentTypeCompatibleWith(MediaType.APPLICATION_JSON))
                .andExpect(jsonPath("$.status").value(404))
                .andExpect(jsonPath("$.error").value("Not Found"))
                .andExpect(jsonPath("$.message")
                        .value("Business not found with id: 999999"))
                .andExpect(jsonPath("$.path")
                        .value("/api/v1/businesses/999999"));
    }

    @Test
    void shouldGetAllBusinesses() throws Exception {
        businessRepository.save(
                new Business("QueueFlow Clinic", "Medical clinic")
        );

        businessRepository.save(
                new Business("QueueFlow Bank", "Banking services")
        );

        mockMvc.perform(get("/api/v1/businesses"))
                .andExpect(status().isOk())
                .andExpect(content().contentTypeCompatibleWith(MediaType.APPLICATION_JSON))
                .andExpect(jsonPath("$.length()").value(2))
                .andExpect(jsonPath("$[0].id").isNumber())
                .andExpect(jsonPath("$[0].name").value("QueueFlow Clinic"))
                .andExpect(jsonPath("$[1].id").isNumber())
                .andExpect(jsonPath("$[1].name").value("QueueFlow Bank"));
    }

    @Test
    void shouldReturnEmptyListWhenNoBusinessesExist() throws Exception {
        mockMvc.perform(get("/api/v1/businesses"))
                .andExpect(status().isOk())
                .andExpect(content().contentTypeCompatibleWith(MediaType.APPLICATION_JSON))
                .andExpect(jsonPath("$").isArray())
                .andExpect(jsonPath("$").isEmpty());
    }
}