<?php
/**
 * SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

namespace OCA\CalendarVisioAddon\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

/**
 * Registers a dedicated entry in the Nextcloud admin sidebar.
 * The entry appears at /settings/admin/calendar_visio_addon with the app icon.
 */
class AdminSection implements IIconSection
{

    public function __construct(
        private readonly IURLGenerator $url,
        private readonly IL10N $l,
    ) {
    }

    /**
     * Section ID — must match AdminSettings::getSection().
     * Becomes the URL slug: /settings/admin/calendar_visio_addon
     */
    public function getId(): string
    {
        return 'calendar_visio_addon';
    }

    public function getName(): string
    {
        return $this->l->t('Calendar Visio Add-on');
    }

    /**
     * Priority in the sidebar (0 = top). 85 places it near other integrations.
     */
    public function getPriority(): int
    {
        return 85;
    }

    /**
     * Icon displayed next to the section name in the sidebar.
     * Points to img/app.svg inside the app directory.
     */
    public function getIcon(): string
    {
        return $this->url->imagePath('calendar_visio_addon', 'app.svg');
    }
}
