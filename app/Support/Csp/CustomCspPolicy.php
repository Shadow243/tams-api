<?php

declare(strict_types=1);

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

/**
 * Class CspPolicy
 *
 * This class defines the Content Security Policy (CSP) for the application.
 * It configures various directives to enhance security by controlling resources
 * that can be loaded and executed in the application.
 */
final class CustomCspPolicy implements Preset
{
    public function configure(Policy $policy): void
    {
        $frontEndDevServer = config('app.frontend_dev_server');

        $policy->add(Directive::SCRIPT, [
            Keyword::SELF,
            $frontEndDevServer,
        ])
            ->add(Directive::STYLE, [
                Keyword::SELF,
                $frontEndDevServer,
            ])
            ->add(Directive::FORM_ACTION, [
                Keyword::SELF,
            ])
            ->add(Directive::IMG, [
                Keyword::SELF,
                $frontEndDevServer,
                'cdn.futatrans.com',
                'data:',
            ])
            ->add(Directive::FONT, [
                Keyword::SELF,
                $frontEndDevServer,
                'data:',
            ])
            ->add(Directive::CONNECT, [
                Keyword::SELF,
                'ws://' . $frontEndDevServer,
                'wss://' . config('broadcasting.connections.pusher.options.host'),
                'wss://ws-eu.pusher.com',
            ])
            ->add(Directive::WORKER, [
                Keyword::SELF,
                $frontEndDevServer,
            ]);
    }
}
