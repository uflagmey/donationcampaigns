/**
 * Donation Campaigns extension for phpBB.
 *
 * Reveals the decimal-places warning once a currency field is touched.
 *
 * The warning applies to the currency code, symbol and decimal places, and to
 * nothing else on the page. Shown permanently it was noise for an
 * administrator who only wanted to change how many donors are listed, and
 * noise is how real warnings stop being read.
 *
 * PROGRESSIVE ENHANCEMENT. This only changes WHEN the warning is seen. The
 * rule it describes is enforced on the server: a decimal-places change on a
 * board with recorded amounts is refused until a confirmation box is ticked.
 * With scripting unavailable the warning simply arrives one step later, on the
 * page the server returns when it refuses -- where the template renders it
 * visible. Nothing can be changed unwarned either way.
 *
 * @copyright (c) 2026 uflagmey
 * @license GNU General Public License, version 2 (GPL-2.0-only)
 */

(function () {
	'use strict';

	var warning = document.getElementById('donationcampaigns_exponent_warning');

	// Absent when the board has no recorded amounts, and already visible when
	// the server is asking for confirmation. Neither has anything to reveal.
	if (!warning || !warning.hidden) {
		return;
	}

	var fields = [
		'donationcampaigns_currency_code',
		'donationcampaigns_currency_symbol',
		'donationcampaigns_currency_exponent'
	];

	function reveal() {
		warning.hidden = false;
	}

	for (var i = 0; i < fields.length; i++) {
		var field = document.getElementById(fields[i]);

		if (field) {
			// 'input' rather than 'change': it fires on the first keystroke,
			// on a paste and on a number spinner, so the warning arrives while
			// the value is being decided rather than after leaving the field.
			field.addEventListener('input', reveal);
		}
	}
}());
