
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
public class QrCredentialEncryptionService {

    private static final String TRANSFORMATION = "AES/GCM/NoPadding";
    private static final int IV_LENGTH = 12;
    private static final int TAG_LENGTH = 128;

    private final SecureRandom secureRandom = new SecureRandom();
    private final SecretKeySpec secretKey;

    public QrCredentialEncryptionService(
            @Value("${queueflow.qr-credential-encryption-key}")
            String encodedKey
    ) {
        byte[] keyBytes;

        try {
            keyBytes = Base64.getDecoder().decode(encodedKey);
        } catch (IllegalArgumentException e) {
            throw new IllegalStateException(
                    "QR_CREDENTIAL_ENCRYPTION_KEY must be valid Base64", e
            );
        }

        if (keyBytes.length != 32) {
            throw new IllegalStateException(
                    "QR_CREDENTIAL_ENCRYPTION_KEY must decode to exactly 32 bytes"
            );
        }

        this.secretKey = new SecretKeySpec(keyBytes, "AES");
    }

    public String encrypt(String plaintext) {
        try {
            byte[] iv = new byte[IV_LENGTH];
            secureRandom.nextBytes(iv);

            Cipher cipher = Cipher.getInstance(TRANSFORMATION);
            cipher.init(
                    Cipher.ENCRYPT_MODE,
                    secretKey,
                    new GCMParameterSpec(TAG_LENGTH, iv)
            );

            byte[] ciphertext = cipher.doFinal(
                    plaintext.getBytes(StandardCharsets.UTF_8)
            );

            byte[] combined = new byte[iv.length + ciphertext.length];
            System.arraycopy(iv, 0, combined, 0, iv.length);
            System.arraycopy(
                    ciphertext, 0,
                    combined, iv.length,
                    ciphertext.length
            );

            return Base64.getUrlEncoder()
                    .withoutPadding()
                    .encodeToString(combined);

        } catch (Exception e) {
            throw new IllegalStateException(
                    "Failed to encrypt QR credential", e
            );
        }
    }

    public String decrypt(String encryptedCredential) {
        try {
            byte[] combined = Base64.getUrlDecoder()
                    .decode(encryptedCredential);

            if (combined.length <= IV_LENGTH + 16) {
                throw new IllegalArgumentException(
                        "Encrypted QR credential is invalid"
                );
            }

            byte[] iv = new byte[IV_LENGTH];
            byte[] ciphertext = new byte[combined.length - IV_LENGTH];

            System.arraycopy(combined, 0, iv, 0, IV_LENGTH);
            System.arraycopy(
                    combined, IV_LENGTH,
                    ciphertext, 0,
                    ciphertext.length
            );

            Cipher cipher = Cipher.getInstance(TRANSFORMATION);
            cipher.init(
                    Cipher.DECRYPT_MODE,
                    secretKey,
                    new GCMParameterSpec(TAG_LENGTH, iv)
            );

            return new String(
                    cipher.doFinal(ciphertext),
                    StandardCharsets.UTF_8
            );

        } catch (Exception e) {
            throw new IllegalStateException(
                    "Failed to decrypt QR credential", e
            );
        }
    }
}
