<?php

namespace Drupal\dmf_core\Crypto;

use DigitalMarketingFramework\Core\Crypto\HashServiceInterface;
use Drupal\Core\Site\Settings;

class HashService implements HashServiceInterface
{
    public function generateHash(string $subject, string $additionalSecret): string
    {
        $key = Settings::getHashSalt();

        return hash_hmac('sha256', $subject, $key . $additionalSecret);
    }

    public function validateHash(string $subject, string $additionalSecret, string $hash): bool
    {
        return hash_equals($this->generateHash($subject, $additionalSecret), $hash);
    }
}
