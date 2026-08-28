<?php
/**
 * SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

namespace OCA\CalendarVisioAddon\AppInfo;

use OCA\CalendarVisioAddon\Service\VisioApiService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Services\IInitialState;
use OCP\L10N\IFactory;
use OCP\Util;

class Application extends App implements IBootstrap
{

    public const APP_ID = 'calendar_visio_addon';

    public function __construct(array $urlParams = [])
    {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void
    {
        // Nothing to register here — InitialState is pushed in boot/registerScripts
    }

    public function boot(IBootContext $context): void
    {
        $context->injectFn([$this, 'registerScripts']);
    }

    public function registerScripts(IInitialState $initialState, VisioApiService $apiService, IFactory $l10nFactory): void
    {
        $initialState->provideInitialState('is_configured', $apiService->isConfigured());
        $l = $l10nFactory->get(self::APP_ID);
        $initialState->provideInitialState('translations', [
            'Create Visio meeting'                    => $l->t('Create Visio meeting'),
            'Creating...'                             => $l->t('Creating...'),
            'Link added'                              => $l->t('Link added'),
            'Failed - retry'                          => $l->t('Failed - retry'),
            'Visio meeting link added to location'    => $l->t('Visio meeting link added to location'),
            'Could not create Visio meeting: {error}' => $l->t('Could not create Visio meeting: {error}'),
            'Unknown error'                           => $l->t('Unknown error'),
        ]);
        Util::addScript(self::APP_ID, 'calendar-visio-plugin');
        Util::addStyle(self::APP_ID, 'calendar-visio-style');
    }
}
