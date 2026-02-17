<?php

namespace Modules\Cookie\Database\Traits;

trait HasCookieSeeder
{
    /**
     * Get the cookie consent page name
     */
    public function getCookiePageName(): string
    {
        return 'Cookie Policy';
    }

    /**
     * Get the cookie consent page content
     */
    public function getCookiePageContent(): string
    {
        $content = '<h3>EU Cookie Consent</h3>';
        $content .= '<p>To use this website we are using Cookies and collecting some Data. To be compliant with the EU GDPR we give you to choose if you allow us to use certain Cookies and to collect some Data.</p>';

        $content .= '<h4>Essential Data</h4>';
        $content .= '<p>The Essential Data is needed to run the Site you are visiting technically. You can not deactivate them.</p>';
        $content .= '<p><strong>Session Cookie:</strong> PHP uses a Cookie to identify user sessions. Without this Cookie the Website is not working.</p>';
        $content .= '<p><strong>XSRF-Token Cookie:</strong> Laravel automatically generates a CSRF "token" for each active user session managed by the application. This token is used to verify that the authenticated user is the one actually making the requests to the application.</p>';

        $content .= '<h4>Analytics Cookies</h4>';
        $content .= '<p>These cookies help us understand how visitors interact with our website by collecting and reporting information anonymously.</p>';

        $content .= '<h4>Marketing Cookies</h4>';
        $content .= '<p>These cookies are used to track visitors across websites. The intention is to display ads that are relevant and engaging for the individual user.</p>';

        return $content;
    }

    /**
     * Get the privacy policy page name
     */
    public function getPrivacyPolicyPageName(): string
    {
        return 'Privacy Policy';
    }

    /**
     * Get the privacy policy page content
     */
    public function getPrivacyPolicyPageContent(): string
    {
        $content = '<h3>Privacy Policy</h3>';
        $content .= '<p>This privacy policy explains how we collect, use, and protect your personal information when you use our website.</p>';

        $content .= '<h4>Information We Collect</h4>';
        $content .= '<p>We collect information that you provide directly to us, such as when you create an account, make a purchase, or contact us for support.</p>';

        $content .= '<h4>How We Use Your Information</h4>';
        $content .= '<p>We use the information we collect to provide, maintain, and improve our services, process transactions, and communicate with you.</p>';

        $content .= '<h4>Data Security</h4>';
        $content .= '<p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>';

        $content .= '<h4>Your Rights</h4>';
        $content .= '<p>Under GDPR, you have the right to access, correct, delete, or restrict the processing of your personal data. You also have the right to data portability and to object to processing.</p>';

        return $content;
    }

    /**
     * Get cookie categories configuration
     */
    public function getCookieCategories(): array
    {
        return [
            'essential' => [
                'required' => true,
                'name' => 'Essential Cookies',
                'description' => 'These cookies are essential for the website to function properly and cannot be disabled.',
                'cookies' => [
                    'XSRF-TOKEN' => 'CSRF protection token',
                    'laravel_session' => 'Session identifier',
                    'cookie_for_consent' => 'Cookie consent preferences',
                ],
            ],
            'analytics' => [
                'required' => false,
                'name' => 'Analytics Cookies',
                'description' => 'These cookies help us understand how visitors interact with the website.',
                'cookies' => [
                    '_ga' => 'Google Analytics - User identification',
                    '_gid' => 'Google Analytics - Session identification',
                    '_gat' => 'Google Analytics - Throttle requests',
                ],
            ],
            'marketing' => [
                'required' => false,
                'name' => 'Marketing Cookies',
                'description' => 'These cookies are used to deliver personalized advertisements.',
                'cookies' => [
                    '_fbp' => 'Facebook Pixel - Track conversions',
                    'fr' => 'Facebook - Ad delivery and targeting',
                ],
            ],
        ];
    }
}
