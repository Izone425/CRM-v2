<?php

namespace App\Services;

class TemplateSelector
{
    protected array $templates = [
        // Bahasa Melayu Campaigns
        '22374404055' => [
            0 => ['email' => 'emails.demo_notification', 'sid' => null],
            1 => ['email' => 'emails.email_blasting_1st', 'sid' => 'HXcc05134b6c74ecc02682a25887978630'],
            2 => ['email' => 'emails.email_blasting_2nd', 'sid' => 'HXbb1b933e2fa363c64c996ae0da7c8773'],
            3 => ['email' => 'emails.email_blasting_3rd', 'sid' => 'HX8094ffaa4380226a4c803c10ea59655e'],
            4 => ['email' => 'emails.email_blasting_4th', 'sid' => 'HX4d2db45f7de1fd07563369d87a0c8c75'],
            5 => ['email' => 'emails.cancel_demo_notification', 'sid' => null],
        ],
        '120213654055070392' => [
            0 => ['email' => 'emails.demo_notification', 'sid' => null],
            1 => ['email' => 'emails.email_blasting_1st', 'sid' => 'HXcc05134b6c74ecc02682a25887978630'],
            2 => ['email' => 'emails.email_blasting_2nd', 'sid' => 'HXbb1b933e2fa363c64c996ae0da7c8773'],
            3 => ['email' => 'emails.email_blasting_3rd', 'sid' => 'HX8094ffaa4380226a4c803c10ea59655e'],
            4 => ['email' => 'emails.email_blasting_4th', 'sid' => 'HX4d2db45f7de1fd07563369d87a0c8c75'],
            5 => ['email' => 'emails.cancel_demo_notification', 'sid' => null],
        ],
        '120220143815230392' => [
            0 => ['email' => 'emails.demo_notification', 'sid' => null],
            1 => ['email' => 'emails.email_blasting_1st', 'sid' => 'HXcc05134b6c74ecc02682a25887978630'],
            2 => ['email' => 'emails.email_blasting_2nd', 'sid' => 'HXbb1b933e2fa363c64c996ae0da7c8773'],
            3 => ['email' => 'emails.email_blasting_3rd', 'sid' => 'HX8094ffaa4380226a4c803c10ea59655e'],
            4 => ['email' => 'emails.email_blasting_4th', 'sid' => 'HX4d2db45f7de1fd07563369d87a0c8c75'],
            5 => ['email' => 'emails.cancel_demo_notification', 'sid' => null],
        ],
        // Default (English)
        'default' => [
            0 => ['email' => 'emails.demo_notification', 'sid' => null],
            1 => ['email' => 'emails.email_blasting_1st', 'sid' => 'HX5c9b745783710d7915fedc4e7e503da0'],
            2 => ['email' => 'emails.email_blasting_2nd', 'sid' => 'HX6531d9c843b71e0a45accd0ce2cfe5f2'],
            3 => ['email' => 'emails.email_blasting_3rd', 'sid' => 'HXcccb50b8124d29d7d21af628b92522d4'],
            4 => ['email' => 'emails.email_blasting_4th', 'sid' => 'HX517e06b8e7ddabea51aa799bfd1987f8'],
            5 => ['email' => 'emails.cancel_demo_notification', 'sid' => null],
        ],
    ];

    public function getTemplate(?string $utmCampaign, int $followUpCount): array
    {
        $campaignKey = array_key_exists($utmCampaign, $this->templates) ? $utmCampaign : 'default';
        return $this->templates[$campaignKey][$followUpCount] ?? $this->templates['default'][1];
    }
}
