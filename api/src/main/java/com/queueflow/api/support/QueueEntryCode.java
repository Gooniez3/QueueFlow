package com.queueflow.api.support;

import java.security.SecureRandom;
import java.util.Locale;

public final class QueueEntryCode {

    private static final String PREFIX = "QF";
    private static final String ALPHABET =
            "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    private static final int PAYLOAD_LENGTH = 12;
    private static final SecureRandom RANDOM = new SecureRandom();

    private QueueEntryCode() {
    }

    public static String generate() {
        StringBuilder code = new StringBuilder(
                PREFIX.length() + PAYLOAD_LENGTH
        );
        code.append(PREFIX);

        for (int index = 0; index < PAYLOAD_LENGTH; index++) {
            code.append(ALPHABET.charAt(
                    RANDOM.nextInt(ALPHABET.length())
            ));
        }

        return code.toString();
    }

    public static String normalize(String code) {
        if (code == null) {
            return "";
        }

        return code
                .strip()
                .toUpperCase(Locale.ROOT)
                .replaceAll("[\\s-]+", "");
    }

    public static boolean isValid(String code) {
        return normalize(code).matches("QF[A-HJ-NP-Z2-9]{12}");
    }

    public static String format(String code) {
        String normalized = normalize(code);

        if (!isValid(normalized)) {
            throw new IllegalArgumentException(
                    "Queue entry code is invalid"
            );
        }

        return normalized.substring(0, 2)
                + "-" + normalized.substring(2, 6)
                + "-" + normalized.substring(6, 10)
                + "-" + normalized.substring(10, 14);
    }
}
