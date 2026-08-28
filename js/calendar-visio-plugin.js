  /**
     * SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
     * SPDX-License-Identifier: AGPL-3.0-or-later
     */
    (function (OCA, OCP) {
        'use strict';
        const APP_ID   = 'calendar_visio_addon';
        const API_BASE = OC.generateUrl('/apps/' + APP_ID + '/api/v1');
        const BTN_ID   = 'visio-meeting-btn';
        const IS_CONFIGURED = (function() { try { return OCP?.InitialState?.loadState?.(APP_ID, 'is_configured') ?? false; } catch(e) { console.warn('[' + APP_ID + '] InitialState not found — plugin inactive'); return false; } })();
        const _TRANS = (function() { try { return OCP?.InitialState?.loadState?.(APP_ID, 'translations') ?? {}; } catch(e) { return {}; } })();
        function tVisio(key, vars) { var str = _TRANS[key] !== undefined ? _TRANS[key] : key; if (vars) { Object.keys(vars).forEach(function(k) { str = str.replace('{' + k + '}', vars[k]); }); } return str; }
        function apiFetch(url, options) {
            return fetch(url, Object.assign({}, options, {
                headers: Object.assign({ 'Content-Type': 'application/json', 'requesttoken': OC.requestToken }, (options || {}).headers),
            }));
        }
        async function createVisioMeeting() {
            const response = await apiFetch(API_BASE + '/meeting/create', { method: 'POST' });
            const data = await response.json();
            if (!response.ok || !data.success) { throw new Error(data.error || tVisio('Unknown error')); }
            return data.meetingUrl;
        }
        function injectButton() {
            if (document.getElementById(BTN_ID)) { return; }
            // EditFull: .property-location wraps the PropertyText component
            // EditSimple: no class on wrapper — target any .property-text textarea that is NOT the description
            const textarea = (
                document.querySelector('.property-location textarea') ||
                document.querySelector('.property-text:not(.property-description) textarea:not(.textarea--description)')
            );
            if (!textarea) { return; }
            const locationContainer = textarea.closest('.property-location') || textarea.closest('.property-text');
            if (!locationContainer) { return; }
            const wrapper = document.createElement('div');
            wrapper.className = 'visio-button-container';
            const btn = document.createElement('button');
            btn.id = BTN_ID; btn.type = 'button';
            btn.className = 'button primary visio-meeting-btn';
            btn.textContent = tVisio('Create Visio meeting');
            btn.addEventListener('click', async function(e) {
                e.preventDefault(); e.stopPropagation();
                await handleClick(btn, textarea);
            });
            wrapper.appendChild(btn);
            locationContainer.after(wrapper);
        }
        async function handleClick(btn, textarea) {
            btn.disabled = true;
            btn.textContent = tVisio('Creating...');
            try {
                const meetingUrl = await createVisioMeeting();
                textarea.value = meetingUrl;
                ['input', 'change'].forEach(function(type) {
                    textarea.dispatchEvent(new Event(type, { bubbles: true }));
                });
                textarea.focus();
                btn.textContent = tVisio('Link added');
                OCP && OCP.Toast && OCP.Toast.success(tVisio('Visio meeting link added to location'));
            } catch (err) {
                btn.textContent = tVisio('Failed - retry');
                OCP && OCP.Toast && OCP.Toast.error(tVisio('Could not create Visio meeting: {error}', { error: err.message }));
            } finally {
                setTimeout(function() { btn.disabled = false; btn.textContent = tVisio('Create Visio meeting'); }, 6000);
            }
        }
        function startObserver() {
            var debounce = null;
            var observer = new MutationObserver(function() {
                clearTimeout(debounce);
                debounce = setTimeout(injectButton, 150);
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
        function init() {
            if (!IS_CONFIGURED) { console.info('[' + APP_ID + '] not configured — plugin inactive'); return; }
            startObserver();
            injectButton();
        }
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
        OCA.CalendarVisioAddon = { createMeeting: createVisioMeeting };
    }(window.OCA = window.OCA || {}, window.OCP = window.OCP || {}));