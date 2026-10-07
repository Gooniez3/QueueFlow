package com.queueflow.api.exception;

public class QrCredentialInactiveException
        extends RuntimeException {

    public QrCredentialInactiveException(
            String message
    ) {
        super(message);
    }
}
