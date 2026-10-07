package com.queueflow.api.realtime;

public enum QueueRealtimeEventType {
    QUEUE_CREATED,
    JOIN,
    CANCEL,
    CALL_NEXT,
    RECALL,
    START_SERVING,
    COMPLETE,
    SKIP,
    PAUSE,
    RESUME,
    CLOSE,
    REOPEN
}