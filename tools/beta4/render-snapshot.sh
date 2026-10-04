#!/usr/bin/env bash
#
# Capture the pages the extension renders on the local Docker board, normalised,
# so two captures can be diffed (1.0.0-beta4, the switch to native Twig).
#
# Development tool, NOT shipped (it lives outside ext/). It complements
# tools/beta4/compare-compiled.php: the compiled comparison proves every
# template branch; this capture covers what only a running board shows —
# includes, asset paths, the template context and row variables, core's
# overall_header/footer around the extension's markup.
#
# Usage (from the repository root, board running):
#   RS_PASSWORD=… tools/beta4/render-snapshot.sh capture <outdir>
#   tools/beta4/render-snapshot.sh diff <dirA> <dirB>
#
# Environment:
#   BOARD_URL    default http://localhost:8081
#   RS_PASSWORD  password of the board's test accounts (user1, admin2); kept
#                out of this file on purpose. Required for capture.
#
# Every state is read-only on the board: GET pages, plus POSTs that cannot
# write — a form that fails validation, a posting preview. The ACP exponent
# confirmation is reached together with an invalid currency code, so it can
# never be saved even if the confirmation rule changed.
#
# Normalisation (per-request noise only, never extension markup): session ids,
# form keys (form_token, creation_time, lastclick), assets_version, the cron
# task named in the cron image, the board clock ("It is currently …"), the
# who-is-online blocks. Two captures of an unchanged board must be identical.
#
# Not covered, because the board's data has no such case (review and the
# compiled comparison only): a shown donation date, "and N others", an account
# with the donations permission alone, the create form (every topic already has
# a campaign), the currency symbol before the amount.
#
# Exit codes: capture 0 ok / 1 a state failed; diff 0 identical / 1 different;
# 64 usage.
#
# Every form is posted at least one second after it was fetched: phpBB's
# check_form_key() refuses a form whose creation_time is the current second
# (includes/functions.php:2128-2131, "not a human").

set -euo pipefail

BOARD_URL="${BOARD_URL:-http://localhost:8081}"
ACP_MODULE='i=-uflagmey-donationcampaigns-acp-main_module'

# name | account | method | path | POST fields (urlencoded, & separated)
# account: guest, user1 (f_donationcampaigns_manage in forum 902 only),
#          admin2 (a_donationcampaigns: manage + donations everywhere),
#          acp (admin2 after ACP re-authentication)
STATES='
g-index|guest|GET|/index.php|
g-topic-1-reached-anonymous|guest|GET|/viewtopic.php?t=1|
g-topic-912-donors-url-desc|guest|GET|/viewtopic.php?t=912|
g-topic-913-bare|guest|GET|/viewtopic.php?t=913|
g-topic-916-disabled|guest|GET|/viewtopic.php?t=916|
g-list-page1|guest|GET|/app.php/donationcampaigns|
g-list-page2|guest|GET|/app.php/donationcampaigns?start=25|
u1-topic-913-tools-manage|user1|GET|/viewtopic.php?t=913|
u1-topic-912-no-right|user1|GET|/viewtopic.php?t=912|
u1-manage-913-manage-only|user1|GET|/app.php/donationcampaigns/topic/913|
u1-manage-916-disabled|user1|GET|/app.php/donationcampaigns/topic/916|
u1-campaign-edit-913|user1|GET|/app.php/donationcampaigns/campaign/913/edit|
u1-campaign-edit-913-error|user1|POST|/app.php/donationcampaigns/campaign/913/edit|campaign_title=Render%20snapshot&target_amount=abc&submit=1
u1-posting-902-panel|user1|GET|/posting.php?mode=post&f=902|
u1-posting-902-preview|user1|POST|/posting.php?mode=post&f=902|subject=Snapshot%20topic&message=Snapshot%20message%20text&preview=1&donationcampaigns_panel=1&donationcampaigns_attach=1&donationcampaigns_campaign_title=&donationcampaigns_target_amount=12,50&donationcampaigns_show_donor_names=1
u1-posting-2-no-panel|user1|GET|/posting.php?mode=post&f=2|
a2-topic-912-manage-button|admin2|GET|/viewtopic.php?t=912|
a2-manage-912-donations|admin2|GET|/app.php/donationcampaigns/topic/912|
a2-manage-913-no-donations|admin2|GET|/app.php/donationcampaigns/topic/913|
a2-donation-add-912|admin2|GET|/app.php/donationcampaigns/campaign/912/donation/add|
a2-donation-add-912-error|admin2|POST|/app.php/donationcampaigns/campaign/912/donation/add|donation_amount=abc&donation_time=2026-10-04&donor_name=Snapshot&submit=1
a2-donation-edit-955-public|admin2|GET|/app.php/donationcampaigns/donation/955/edit|
a2-donation-edit-953-anonymous|admin2|GET|/app.php/donationcampaigns/donation/953/edit|
acp-settings|acp|GET|/adm/index.php?ACP&mode=settings|
acp-settings-error|acp|POST|/adm/index.php?ACP&mode=settings|donationcampaigns_currency_code=E1&donationcampaigns_currency_symbol=%E2%82%AC&donationcampaigns_currency_symbol_before=0&donationcampaigns_currency_symbol_space=1&donationcampaigns_currency_exponent=2&donationcampaigns_donor_list_limit=50&donationcampaigns_list_enabled=1&submit=1
acp-settings-error-confirm|acp|POST|/adm/index.php?ACP&mode=settings|donationcampaigns_currency_code=E1&donationcampaigns_currency_symbol=%E2%82%AC&donationcampaigns_currency_symbol_before=0&donationcampaigns_currency_symbol_space=1&donationcampaigns_currency_exponent=3&donationcampaigns_donor_list_limit=50&donationcampaigns_list_enabled=1&submit=1
acp-campaigns-page1|acp|GET|/adm/index.php?ACP&mode=campaigns|
acp-campaigns-page2|acp|GET|/adm/index.php?ACP&mode=campaigns&start=25|
acp-donations-912|acp|GET|/adm/index.php?ACP&mode=donations&campaign_id=912|
acp-donations-913-empty|acp|GET|/adm/index.php?ACP&mode=donations&campaign_id=913|
'

usage()
{
	sed -n '2,/^set -euo/p' "$0" | sed '$d' >&2
	exit 64
}

# Hidden inputs of the first form on stdin whose action contains $1, as
# name=value lines (entities decoded), ready for curl --data-urlencode.
hidden_fields()
{
	perl -0777 -e '
		my $needle = shift;
		local $_ = <STDIN>;
		while (/(<form\b[^>]*>)(.*?)<\/form>/sg) {
			my ($open, $body) = ($1, $2);
			next unless $open =~ /action="([^"]*)"/ && index($1, $needle) >= 0;
			while ($body =~ /<input\b([^>]*)>/sg) {
				my $attrs = $1;
				next unless $attrs =~ /type="hidden"/;
				my ($name) = $attrs =~ /name="([^"]*)"/;
				my ($value) = $attrs =~ /value="([^"]*)"/;
				$value //= "";
				$value =~ s/&amp;/&/g; $value =~ s/&quot;/"/g; $value =~ s/&#0?39;/\x27/g;
				$value =~ s/&lt;/</g; $value =~ s/&gt;/>/g;
				print "$name=$value\n" if defined $name;
			}
			last;
		}
	' "$1"
}

session_id()
{
	awk '$6 ~ /_sid$/ { sid = $7 } END { print sid }' "$1"
}

login()
{
	local account="$1" jar="$2" page args=()
	page="$(curl -sS -c "$jar" -b "$jar" "$BOARD_URL/ucp.php?mode=login")"
	while IFS= read -r field; do args+=(--data-urlencode "$field"); done < <(printf '%s' "$page" | hidden_fields 'mode=login')
	sleep 1
	page="$(curl -sS -c "$jar" -b "$jar" "${args[@]}" \
		--data-urlencode "username=$account" --data-urlencode "password=$RS_PASSWORD" \
		--data-urlencode 'login=Login' "$BOARD_URL/ucp.php?mode=login")"
	# Read the whole page first: grep -q would close the pipe early and, under
	# pipefail, turn curl's SIGPIPE into a false login failure.
	page="$(curl -sS -c "$jar" -b "$jar" "$BOARD_URL/index.php")"
	case "$page" in
		*mode=logout*) ;;
		*) echo "render-snapshot: login as $account failed" >&2; return 1 ;;
	esac
}

acp_login()
{
	local jar="$1" sid page password_field args=()
	sid="$(session_id "$jar")"
	page="$(curl -sS -c "$jar" -b "$jar" "$BOARD_URL/adm/index.php?sid=$sid")"
	password_field="$(printf '%s' "$page" | grep -oE 'name="password_[0-9a-f]+"' | head -n 1 | cut -d'"' -f2)"
	[ -n "$password_field" ] || { echo "render-snapshot: no ACP login form" >&2; return 1; }
	while IFS= read -r field; do args+=(--data-urlencode "$field"); done < <(printf '%s' "$page" | hidden_fields 'index.php')
	sleep 1
	curl -sS -c "$jar" -b "$jar" "${args[@]}" \
		--data-urlencode 'username=admin2' --data-urlencode "$password_field=$RS_PASSWORD" \
		--data-urlencode 'login=Login' "$BOARD_URL/adm/index.php?sid=$sid" > /dev/null
}

normalise()
{
	perl -0777 -pe '
		s/(sid(?:=|&#x3D;|%3D|\\u003D))[0-9a-f]{32}/${1}SID/g;
		s/(name="sid" value=")[0-9a-f]{32}/${1}SID/g;
		s/(name="(?:form_token|creation_time|lastclick)" value=")[^"]*/${1}X/g;
		s/assets_version=\d+/assets_version=N/g;
		s/cron\.task\.[a-z_.]+/cron.task.X/g;
		s/(<p class="right responsive-center time[^"]*">).*?(<\/p>)/${1}CLOCK${2}/sg;
		s/(<p class="responsive-center time[^"]*">).*?(<\/p>)/${1}CLOCK${2}/sg;
		s/(<div class="stat-block online-list">).*?(<\/div>)/${1}ONLINE${2}/sg;
	'
}

capture()
{
	local out="$1" failed=0
	[ -n "${RS_PASSWORD:-}" ] || { echo "render-snapshot: RS_PASSWORD is not set" >&2; exit 64; }
	# Global, not local: the EXIT trap runs after this function has returned.
	tmp="$(mktemp -d)"
	trap 'rm -rf "$tmp"' EXIT
	mkdir -p "$out"

	login user1 "$tmp/user1.jar"
	login admin2 "$tmp/admin2.jar"
	# A session of its own: the ACP re-authentication replaces the session it
	# runs in, so sharing admin2's would log the frontend states out.
	login admin2 "$tmp/acp.jar"
	acp_login "$tmp/acp.jar"

	while IFS='|' read -r name account method path fields; do
		[ -n "$name" ] || continue
		local jar=() url status args=() page sid needle
		if [ "$account" != guest ]; then jar=(-c "$tmp/$account.jar" -b "$tmp/$account.jar"); fi
		url="$BOARD_URL$path"
		if [ "$account" = acp ]; then
			sid="$(session_id "$tmp/acp.jar")"
			url="${url/ACP/$ACP_MODULE&sid=$sid}"
		fi

		if [ "$method" = POST ]; then
			# The form keys come from the same page, fetched first. The form is
			# found by the last segment of its action (posting.php, edit, add,
			# index.php).
			needle="${path%%\?*}"
			needle="${needle##*/}"
			page="$(curl -sS ${jar[@]+"${jar[@]}"} "$url")"
			while IFS= read -r field; do args+=(--data-urlencode "$field"); done \
				< <(printf '%s' "$page" | hidden_fields "$needle" | grep -E '^(form_token|creation_time|lastclick|sid)=')
			IFS='&' read -r -a pairs <<< "$fields"
			for pair in "${pairs[@]}"; do args+=(--data "$pair"); done
			sleep 1
		fi

		status="$(curl -sS -o "$tmp/page.html" -w '%{http_code}' ${jar[@]+"${jar[@]}"} ${args[@]+"${args[@]}"} "$url")"
		{ echo "<!-- state: $name | $account | $method | $path | HTTP $status -->"; normalise < "$tmp/page.html"; } > "$out/$name.html"

		if [ "$status" != 200 ] || grep -qE 'General Error|SQL ERROR|Fatal error|Twig\\Error|\[phpBB Debug\]' "$tmp/page.html"; then
			echo "FAIL $name (HTTP $status)" >&2
			failed=1
		else
			echo "ok   $name"
		fi
	done <<< "$STATES"

	return "$failed"
}

case "${1:-}" in
	capture) [ "$#" -eq 2 ] || usage; capture "$2" ;;
	diff) [ "$#" -eq 3 ] || usage; diff -ru "$2" "$3" && echo "render-snapshot: identical" ;;
	*) usage ;;
esac
