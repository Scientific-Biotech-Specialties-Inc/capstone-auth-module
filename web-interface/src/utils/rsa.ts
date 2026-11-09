import JSEncrypt from 'jsencrypt'
import api from '@/lib/api'

export interface EncryptionKeyResponse {
  public_key: string
  key_id: string
}

interface CachedKey {
  publicKey: string
  keyId: string
  fetchedAt: number
}

const KEY_TTL_MS = 5 * 60 * 1000;

let cachedKey: CachedKey | null = null;

function isKeyExpired(key: CachedKey): boolean {
  return Date.now() - key.fetchedAt >= KEY_TTL_MS;
}

export function clearEncryptionKeyCache(): void {
  cachedKey = null;
}

export async function fetchEncryptionKey(forceRefresh = false): Promise<EncryptionKeyResponse> {
  if (cachedKey && !isKeyExpired(cachedKey) && !forceRefresh) {
    return {
      public_key: cachedKey.publicKey,
      key_id: cachedKey.keyId,
    };
  }

  const response = await api.get('/api/encryption-key')
  const data: EncryptionKeyResponse = response.data;

  cachedKey = {
    publicKey: data.public_key,
    keyId: data.key_id,
    fetchedAt: Date.now(),
  };

  return data;
}

export function encryptPayload(payload: string, publicKey: string): string {
  const encryptor = new JSEncrypt()
  encryptor.setPublicKey(publicKey)
  const encrypted = encryptor.encrypt(payload)

  if (!encrypted) {
    throw new Error('Encryption failed')
  }

  return encrypted as string
}
