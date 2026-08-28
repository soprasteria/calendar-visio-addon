<?php
/**
 * SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/** @var array $_ */
$apiUrl       = $_['apiUrl']       ?? '';
$clientId     = $_['clientId']     ?? '';
$clientSecret = $_['clientSecret'] ?? '';
?>
<div id="calendar-visio-addon-admin">
    <h2><?php p($l->t('Calendar Visio Add-on')); ?></h2>
    <p class="settings-hint">
        <?php p($l->t('Configure the Visio (Meet) API endpoint and OAuth2 credentials used to generate meeting links on behalf of users.')); ?>
    </p>

    <form id="visio-admin-form">
        <div class="visio-setting-row">
            <label for="visio-api-url"><?php p($l->t('Visio API Base URL')); ?></label>
            <input type="url" id="visio-api-url" name="apiUrl"
                   value="<?php p($apiUrl); ?>"
                   placeholder="https://visio.example.org"
                   class="input-wide" />
            <p class="settings-hint"><?php p($l->t('Base URL of the Meet instance (e.g. https://visio.example.org). Do not include /api or /external-api.')); ?></p>
        </div>

        <div class="visio-setting-row">
            <label for="visio-client-id"><?php p($l->t('OAuth2 Client ID')); ?></label>
            <input type="text" id="visio-client-id" name="clientId"
                   value="<?php p($clientId); ?>"
                   placeholder="nextcloud-addon"
                   class="input-wide" />
            <p class="settings-hint"><?php p($l->t('client_id of the Application registered in Meet (Django Admin).')); ?></p>
        </div>

        <div class="visio-setting-row">
            <label for="visio-client-secret"><?php p($l->t('OAuth2 Client Secret')); ?></label>
            <input type="password" id="visio-client-secret" name="clientSecret"
                   value="<?php p($clientSecret); ?>"
                   placeholder="<?php p($clientSecret !== '' ? $l->t('Leave empty to keep existing secret') : ''); ?>"
                   autocomplete="new-password"
                   class="input-wide" />
            <p class="settings-hint"><?php p($l->t('Leave empty to keep the existing secret.')); ?></p>
        </div>

        <button type="submit" class="button primary"><?php p($l->t('Save')); ?></button>
        <span id="visio-admin-msg" class="msg" style="display:none;"></span>
    </form>
</div>
