package com.queueflow.api.entity;

public enum QueueEntryStatus {
    WAITING,
    CALLED,
    SERVING,
    COMPLETED,
    CANCELLED,
    SKIPPED
}