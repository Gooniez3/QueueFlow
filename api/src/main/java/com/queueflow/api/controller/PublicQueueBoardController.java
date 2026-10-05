package com.queueflow.api.controller;

import com.queueflow.api.response.PublicQueueBoardResponse;
import com.queueflow.api.service.PublicQueueBoardService;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/api/v1/queues/{queueId}/board")
public class PublicQueueBoardController {

    private final PublicQueueBoardService publicQueueBoardService;

    public PublicQueueBoardController(
            PublicQueueBoardService publicQueueBoardService
    ) {
        this.publicQueueBoardService = publicQueueBoardService;
    }

    @GetMapping
    public ResponseEntity<PublicQueueBoardResponse> getBoard(
            @PathVariable Long queueId
    ) {
        return ResponseEntity.ok(
                publicQueueBoardService.getBoard(queueId)
        );
    }
}