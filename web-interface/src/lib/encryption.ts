import * as CryptoJS from 'crypto-js';

const rawKey = import.meta.env.VITE_INTERNAL_ENCRYPTION_KEY;
const KEY = (rawKey && rawKey.length === 32)
  ? rawKey
  : '12345678901234567890123456789012';

if (!rawKey || rawKey.length !== 32) {
  if (import.meta.env.DEV) {
    console.warn(
      '[encryption] VITE_INTERNAL_ENCRYPTION_KEY is missing or not 32 characters; using development fallback key.'
    );
  }
}

/**
 * Encrypts a JSON object into a base64 string: base64(iv + ciphertext)
 * matching the PHP openssl_encrypt implementation.
 */
export const encryptPayload = (data: any): string => {
  const plaintext = JSON.stringify(data);
  
  // Generate a random 16-byte IV
  const iv = CryptoJS.lib.WordArray.random(16);
  const keyHex = CryptoJS.enc.Utf8.parse(KEY);

  const encrypted = CryptoJS.AES.encrypt(plaintext, keyHex, {
    iv: iv,
    mode: CryptoJS.mode.CBC,
    padding: CryptoJS.pad.Pkcs7
  });

  // Prepend IV to ciphertext (raw word arrays)
  const ivAndCiphertext = iv.concat(encrypted.ciphertext);

  // Return base64 encoded string
  return CryptoJS.enc.Base64.stringify(ivAndCiphertext);
};
