package com.queueflow.api.controller;

import com.queueflow.api.support.QueueEntryCode;
import org.junit.jupiter.api.Test;

import static org.assertj.core.api.Assertions.assertThat;
import static org.assertj.core.api.Assertions.assertThatThrownBy;

class QueueEntryCodeTest {

    @Test
    void shouldNormalizeSupportedFormatting() {
        assertThat(QueueEntryCode.normalize("  qf abcd-efgh jklm  "))
                .isEqualTo("QFABCDEFGHJKLM");
        assertThat(QueueEntryCode.format("qf abcd-efgh jklm"))
                .isEqualTo("QF-ABCD-EFGH-JKLM");
    }

    @Test
    void shouldRejectMalformedAndAmbiguousCodes() {
        assertThat(QueueEntryCode.isValid("QF-ABCD-EFGH-JKLO"))
                .isFalse();
        assertThat(QueueEntryCode.isValid("QF-ABCD-EFGH-JKL1"))
                .isFalse();
        assertThat(QueueEntryCode.isValid("not-a-code"))
                .isFalse();
        assertThatThrownBy(() -> QueueEntryCode.format("not-a-code"))
                .isInstanceOf(IllegalArgumentException.class);
    }

    @Test
    void shouldGenerateValidCompactCodes() {
        String code = QueueEntryCode.generate();

        assertThat(code).matches("QF[A-HJ-NP-Z2-9]{12}");
        assertThat(QueueEntryCode.format(code))
                .matches("QF-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}");
    }
}
