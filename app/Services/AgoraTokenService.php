<?php

namespace App\Services;

use RuntimeException;

class AgoraTokenService
{
    private const VERSION = '006';
    private const PRIVILEGE_JOIN_CHANNEL = 1;
    private const PRIVILEGE_PUBLISH_AUDIO = 2;

    public function rtcToken(string $channelName, int $uid, ?int $ttl = null): array
    {
        $appId = (string) config('services.agora.app_id');
        $certificate = (string) config('services.agora.app_certificate');
        $ttl ??= (int) config('services.agora.token_ttl', 3600);

        if ($appId === '' || $certificate === '') {
            throw new RuntimeException('Agora voice calling is not configured.');
        }

        $expireAt = time() + max(60, $ttl);
        $salt = random_int(1, 99999999);
        $privileges = [
            self::PRIVILEGE_JOIN_CHANNEL => $expireAt,
            self::PRIVILEGE_PUBLISH_AUDIO => $expireAt,
        ];

        $message = $this->packUint32($salt)
            . $this->packUint32($expireAt)
            . $this->packPrivileges($privileges);

        $signature = hash_hmac('sha256', $appId . $channelName . $uid . $message, $certificate, true);
        $content = $this->packString($signature)
            . $this->packUint32($this->unsignedCrc32($channelName))
            . $this->packUint32($this->unsignedCrc32((string) $uid))
            . $this->packString($message);

        return [
            'app_id' => $appId,
            'channel_name' => $channelName,
            'token' => self::VERSION . $appId . base64_encode($content),
            'uid' => $uid,
            'expires_at' => $expireAt,
        ];
    }

    private function packPrivileges(array $privileges): string
    {
        $packed = pack('v', count($privileges));

        foreach ($privileges as $privilege => $expireAt) {
            $packed .= pack('v', (int) $privilege) . $this->packUint32((int) $expireAt);
        }

        return $packed;
    }

    private function packString(string $value): string
    {
        return pack('v', strlen($value)) . $value;
    }

    private function packUint32(int $value): string
    {
        return pack('V', $value);
    }

    private function unsignedCrc32(string $value): int
    {
        return (int) sprintf('%u', crc32($value));
    }
}
