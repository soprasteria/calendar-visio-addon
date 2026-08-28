/**
 * SPDX-FileCopyrightText: "Copyright 2026, Sopra Steria France"
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Admin settings form handler for Calendar Visio Add-on.
 * Loaded as an external script to comply with Nextcloud's CSP (no inline scripts).
 */
(function () {
	'use strict';

	const APP_ID = 'calendar_visio_addon';

	function initAdminForm() {
		var form = document.getElementById('visio-admin-form');
		if (!form) {
			return; // not on the admin settings page
		}

		form.addEventListener('submit', async function (e) {
			e.preventDefault();

			var msg  = document.getElementById('visio-admin-msg');
			var data = new FormData(form);

			try {
				var res  = await fetch(OC.generateUrl('/apps/' + APP_ID + '/admin/settings'), {
					method:  'POST',
					headers: { 'requesttoken': OC.requestToken },
					body:    data,
				});
				var json = await res.json();
				msg.textContent = json.success ? t(APP_ID, 'Saved') : (json.error || t(APP_ID, 'Save failed'));
				msg.className   = json.success ? 'msg success' : 'msg error';
			} catch (_) {
				msg.textContent = t(APP_ID, 'Save failed');
				msg.className   = 'msg error';
			}

			msg.style.display = 'inline';
			setTimeout(function () { msg.style.display = 'none'; }, 4000);
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAdminForm);
	} else {
		initAdminForm();
	}
}());
