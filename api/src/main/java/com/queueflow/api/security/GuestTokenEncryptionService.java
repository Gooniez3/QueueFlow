package com.queueflow.api.security;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;

import javax.crypto.Cipher;
import javax.crypto.spec.GCMParameterSpec;
import javax.crypto.spec.SecretKeySpec;
import java.nio.charset.StandardCharsets;
import java.security.SecureRandom;
import java.util.Base64;

@Service
public class GuestTokenEncryptionService {

    private static final String TRANSFORMATION = "AES/GCM/NoPadding";
    private static final int IV_LENGTH_BYTES = 12;
    private static final int GCM_TAG_LENGTH_BITS = 128;

    private static final SecureRandom SECURE_RANDOM =
            new SecureRandom();

    private final SecretKeySpec secretKey;

    public GuestTokenEncryptionService(
            @Value("${queueflow.guest-token-encryption-key}")
            String encodedKey
    ) {
        byte[] keyBytes;

        try {
            keyBytes = Base64.getDecoder().decode(encodedKey);
        } catch (IllegalArgumentException exception) {
            throw new IllegalStateException(
                    "GUEST_TOKEN_ENCRYPTION_KEY must be valid Base64",
                    exception
            );
        }

        if (keyBytes.length != 32) {
            throw new IllegalStateException(
                    "GUEST_TOKEN_ENCRYPTION_KEY must decode to exactly 32 bytes"
            );
        }

        this.secretKey =
                new SecretKeySpec(keyBytes, "AES");
    }

    public String encrypt(String plaintext) {
        try {
            byte[] iv = new byte[IV_LENGTH_BYTES];
            SECURE_RANDOM.nextBytes(iv);

            Cipher cipher =
                    Cipher.getInstance(TRANSFORMATION);

            cipher.init(
                    Cipher.ENCRYPT_MODE,
                    secretKey,
                    new GCMParameterSpec(
                            GCM_TAG_LENGTH_BITS,
                            iv
                    )
            );

            byte[] ciphertext =
                    cipher.doFinal(
                            plaintext.getBytes(StandardCharsets.UTF_8)
                    );

            byte[] combined =
                    new byte[iv.length + ciphertext.length];

            System.arraycopy(
                    iv,
                    0,
                    combined,
                    0,
                    iv.length
            );

            System.arraycopy(
                    ciphertext,
                    0,
                    combined,
                    iv.length,
                    ciphertext.length
            );

            return Base64.getUrlEncoder()
                    .withoutPadding()
                    .encodeToString(combined);

        } catch (Exception exception) {
            throw new IllegalStateException(
                    "Failed to encrypt guest token",
                    exception
            );
        }
    }

    public String decrypt(String encryptedToken) {
        try {
            byte[] combined =
                    Base64.getUrlDecoder()
                            .decode(encryptedToken);

            if (combined.length <= IV_LENGTH_BYTES) {
                throw new IllegalArgumentException(
                        "Encrypted guest token is invalid"
                );
            }

            byte[] iv =
                    new byte[IV_LENGTH_BYTES];

            byte[] ciphertext =
                    new byte[
                            combined.length
                                    - IV_LENGTH_BYTES
                            ];

            System.arraycopy(
                    combined,
                    0,
                    iv,
                    0,
                    IV_LENGTH_BYTES
            );

            System.arraycopy(
                    combined,
                    IV_LENGTH_BYTES,
                    ciphertext,
                    0,
                    ciphertext.length
            );

            Cipher cipher =
                    Cipher.getInstance(TRANSFORMATION);

            cipher.init(
                    Cipher.DECRYPT_MODE,
                    secretKey,
                    new GCMParameterSpec(
                            GCM_TAG_LENGTH_BITS,
                            iv
                    )
            );

            byte[] plaintext =
                    cipher.doFinal(ciphertext);

            return new String(
                    plaintext,
                    StandardCharsets.UTF_8
            );

        } catch (Exception exception) {
            throw new IllegalStateException(
                    "Failed to decrypt guest token",
                    exception
            );
        }
    }
}