<?php

namespace Motomedialab\Smtp2Go\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Motomedialab\Smtp2Go\Transports\Smtp2GoTransport;

class Smtp2GoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Dynamically set MAIL_FROM_ADDRESS based on APP_URL subdomain
        $this->setDynamicFromAddress();
        
        // register the smtp2go driver
        Mail::extend(
            'smtp2go',
            fn (array $config = []) => app(Smtp2GoTransport::class)
        );
    }
    
    /**
     * Set dynamic MAIL_FROM_ADDRESS based on APP_URL subdomain
     */
    protected function setDynamicFromAddress(): void
    {
        $appUrl = config('app.url');
        
        if ($appUrl) {
            // Parse the URL to extract the subdomain
            $parsedUrl = parse_url($appUrl);
            $host = $parsedUrl['host'] ?? '';
            
            // Extract subdomain (everything before the first dot)
            $subdomain = explode('.', $host)[0] ?? '';
            
            // Only proceed if we have a valid subdomain
            if ($subdomain && $subdomain !== 'www' && $subdomain !== 'localhost' && strlen($subdomain) > 0) {
                // Set the dynamic MAIL_FROM_ADDRESS
                $dynamicFromAddress = $subdomain . '@bmpweb.dev';
                
                // Override the mail configuration
                Config::set('mail.from.address', $dynamicFromAddress);
                
                // Also set it in the environment for consistency (if putenv is available)
                if (function_exists('putenv')) {
                    putenv("MAIL_FROM_ADDRESS={$dynamicFromAddress}");
                }
                $_ENV['MAIL_FROM_ADDRESS'] = $dynamicFromAddress;
                
                // Log the dynamic email address for debugging
                \Log::info('Dynamic MAIL_FROM_ADDRESS set', [
                    'app_url' => $appUrl,
                    'subdomain' => $subdomain,
                    'from_address' => $dynamicFromAddress
                ]);
            } else {
                // Fallback to a default if no valid subdomain is found
                $fallbackAddress = 'noreply@bmpweb.dev';
                Config::set('mail.from.address', $fallbackAddress);
                if (function_exists('putenv')) {
                    putenv("MAIL_FROM_ADDRESS={$fallbackAddress}");
                }
                $_ENV['MAIL_FROM_ADDRESS'] = $fallbackAddress;
                
                \Log::warning('No valid subdomain found, using fallback MAIL_FROM_ADDRESS', [
                    'app_url' => $appUrl,
                    'fallback_address' => $fallbackAddress
                ]);
            }
        }
    }
}
