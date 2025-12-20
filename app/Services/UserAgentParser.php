<?php

namespace App\Services;

class UserAgentParser
{
    private string $userAgent;

    public function __construct(string $userAgent)
    {
        $this->userAgent = strtolower($userAgent);
    }

    /**
     * Detect device type from user agent
     */
    public function getDeviceType(): string
    {
        if ($this->isMobile()) {
            return 'mobile';
        } elseif ($this->isTablet()) {
            return 'tablet';
        }
        return 'desktop';
    }

    /**
     * Detect browser from user agent
     */
    public function getBrowser(): ?string
    {
        if (strpos($this->userAgent, 'edge') !== false || strpos($this->userAgent, 'edg/') !== false) {
            return 'Edge';
        } elseif (strpos($this->userAgent, 'chrome') !== false && strpos($this->userAgent, 'chromium') === false) {
            return 'Chrome';
        } elseif (strpos($this->userAgent, 'safari') !== false && strpos($this->userAgent, 'chrome') === false) {
            return 'Safari';
        } elseif (strpos($this->userAgent, 'firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($this->userAgent, 'msie') !== false || strpos($this->userAgent, 'trident/') !== false) {
            return 'Internet Explorer';
        } elseif (strpos($this->userAgent, 'opera') !== false || strpos($this->userAgent, 'opr/') !== false) {
            return 'Opera';
        }
        return null;
    }

    /**
     * Detect operating system from user agent
     */
    public function getOs(): ?string
    {
        if (strpos($this->userAgent, 'windows') !== false) {
            return 'Windows';
        } elseif (strpos($this->userAgent, 'macintosh') !== false || strpos($this->userAgent, 'mac os x') !== false) {
            return 'macOS';
        } elseif (strpos($this->userAgent, 'linux') !== false && strpos($this->userAgent, 'android') === false) {
            return 'Linux';
        } elseif (strpos($this->userAgent, 'android') !== false) {
            return 'Android';
        } elseif (strpos($this->userAgent, 'iphone') !== false || strpos($this->userAgent, 'ipad') !== false || strpos($this->userAgent, 'ipod') !== false) {
            return 'iOS';
        }
        return null;
    }

    /**
     * Check if device is mobile
     */
    private function isMobile(): bool
    {
        $mobileAgents = ['iphone', 'android', 'blackberry', 'webos', 'windows phone'];
        foreach ($mobileAgents as $agent) {
            if (strpos($this->userAgent, $agent) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if device is tablet
     */
    private function isTablet(): bool
    {
        $tabletAgents = ['ipad', 'android', 'tablet', 'kindle', 'playbook'];
        foreach ($tabletAgents as $agent) {
            if (strpos($this->userAgent, $agent) !== false) {
                // Make sure it's not a phone
                if (!$this->isMobile()) {
                    return true;
                }
            }
        }
        return false;
    }
}
