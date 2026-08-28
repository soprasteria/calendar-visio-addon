//SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
//SPDX-License-Identifier: AGPL-3.0-or-later
// Guard: OC.L10n may not be initialized yet in Nextcloud 28+ deferred loading
if (typeof OC !== 'undefined' && OC.L10n && typeof OC.L10n.register === 'function') {
    OC.L10n.register(
        'calendar_visio_addon',
        {
            "Create Visio meeting": "Create Visio meeting",
            "Creating...": "Creating...",
            "Unknown error": "Unknown error",
            "Visio meeting link added to location": "Visio meeting link added to location",
            "Could not create Visio meeting: {error}": "Could not create Visio meeting: {error}",
            "Link added": "Link added",
            "Failed - retry": "Failed - retry",
            "Calendar Visio Add-on": "Calendar Visio Add-on",
            "Configure the Visio (Meet) API endpoint and OAuth2 credentials used to generate meeting links on behalf of users.": "Configure the Visio (Meet) API endpoint and OAuth2 credentials used to generate meeting links on behalf of users.",
            "Visio API Base URL": "Visio API Base URL",
            "Base URL of the Meet instance (e.g. https://visio.example.org). Do not include /api or /external-api.": "Base URL of the Meet instance (e.g. https://visio.example.org). Do not include /api or /external-api.",
            "OAuth2 Client ID": "OAuth2 Client ID",
            "client_id of the Application registered in Meet (Django Admin).": "client_id of the Application registered in Meet (Django Admin).",
            "OAuth2 Client Secret": "OAuth2 Client Secret",
            "Leave empty to keep the existing secret.": "Leave empty to keep the existing secret.",
            "Leave empty to keep existing secret": "Leave empty to keep existing secret",
            "Save": "Save",
            "Saved": "Saved",
            "Save failed": "Save failed"
        },
        "nplurals=2; plural=(n != 1);"
    );
}
