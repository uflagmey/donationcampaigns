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
#   COMPOSE_FILE default Testboard_donation/docker-compose.yml
#   RS_PASSWORD  password of the board's test accounts (user1, admin2,
#                cashier1); kept out of this file on purpose. Required for
#                capture.
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
# Board data: the states need what tools/beta4/seed-board.sh adds (cashier1,
# a topic without a campaign, dated and many donations). Ids the seed created
# are looked up at capture time ({SEED_…} in the paths below).
#
# The currency symbol before the amount is a board-wide setting. Its states
# (STATES_SYMBOL_BEFORE) are captured after switching the setting with
# phpBB's own CLI (config:set); the previous value is restored afterwards,
# also when the capture fails. This is the one write the tool makes.
#
# A state counts as captured only if the page answers HTTP 200, contains every
# marker of its "expect" field, and is not a phpBB error, maintenance or login
# page (see page_problem); a logged-in state must still be logged in. The first
# state that fails stops the whole run: a capture with a bad state in it is not
# a capture.
#
# phpBB's CLI runs as www-data, as the board's README requires: run as root, it
# can leave cache/ owned by root, and the board then fails to write its cache.
#
# Exit codes: capture 0 ok / 1 a state failed (run stopped); diff 0 identical /
# 1 different; 64 usage.
#
# Every form is posted at least one second after it was fetched: phpBB's
# check_form_key() refuses a form whose creation_time is the current second
# (includes/functions.php:2128-2131, "not a human").

set -euo pipefail

BOARD_URL="${BOARD_URL:-http://localhost:8081}"
COMPOSE_FILE="${COMPOSE_FILE:-Testboard_donation/docker-compose.yml}"
SYMBOL_BEFORE_KEY='donationcampaigns_currency_symbol_before'
ACP_MODULE='i=-uflagmey-donationcampaigns-acp-main_module'

# name | account | method | path | expect | POST fields (urlencoded, & separated)
# expect: strings the page must contain (all of them, separated by ";"); they
#         identify the expected page, so an error, a login or a maintenance
#         page with HTTP 200 is not taken for it.
# account: guest, user1 (f_donationcampaigns_manage in forum 902 only),
#          cashier1 (f_donationcampaigns_donations in forum 902 only),
#          admin2 (a_donationcampaigns: manage + donations everywhere),
#          acp (admin2 after ACP re-authentication)
STATES='
g-index|guest|GET|/index.php|donationcampaigns.css;class="icon fa-heart fa-fw icon-gray"|
g-topic-1-reached-anonymous|guest|GET|/viewtopic.php?t=1|class="panel donationcampaigns-panel";donationcampaigns-reached|
g-topic-912-donors-url-desc|guest|GET|/viewtopic.php?t=912|class="panel donationcampaigns-panel";donationcampaigns-donors|
g-topic-913-bare|guest|GET|/viewtopic.php?t=913|class="panel donationcampaigns-panel"|
g-topic-916-disabled|guest|GET|/viewtopic.php?t=916|<title>Disabled campaign topic|
g-list-page1|guest|GET|/app.php/donationcampaigns|class="table1 donationcampaigns-list"|
g-list-page2|guest|GET|/app.php/donationcampaigns?start=25|class="table1 donationcampaigns-list"|
u1-topic-913-tools-manage|user1|GET|/viewtopic.php?t=913|class="panel donationcampaigns-panel";donationcampaigns-manage|
u1-topic-912-no-right|user1|GET|/viewtopic.php?t=912|class="panel donationcampaigns-panel"|
u1-manage-913-manage-only|user1|GET|/app.php/donationcampaigns/topic/913|<dl class="details">|
u1-manage-916-disabled|user1|GET|/app.php/donationcampaigns/topic/916|<dl class="details">|
u1-campaign-edit-913|user1|GET|/app.php/donationcampaigns/campaign/913/edit|id="donationcampaigns_campaign"|
u1-campaign-edit-913-error|user1|POST|/app.php/donationcampaigns/campaign/913/edit|id="donationcampaigns_campaign";class="errorbox"|campaign_title=Render%20snapshot&target_amount=abc&submit=1
u1-posting-902-panel|user1|GET|/posting.php?mode=post&f=902|id="donationcampaigns-panel"|
u1-posting-902-preview|user1|POST|/posting.php?mode=post&f=902|id="donationcampaigns-panel";id="preview"|subject=Snapshot%20topic&message=Snapshot%20message%20text&preview=1&donationcampaigns_panel=1&donationcampaigns_attach=1&donationcampaigns_campaign_title=&donationcampaigns_target_amount=12,50&donationcampaigns_show_donor_names=1
u1-posting-2-no-panel|user1|GET|/posting.php?mode=post&f=2|id="postform"|
a2-topic-912-manage-button|admin2|GET|/viewtopic.php?t=912|class="panel donationcampaigns-panel";donationcampaigns-manage|
a2-manage-912-donations|admin2|GET|/app.php/donationcampaigns/topic/912|<dl class="details">;donationcampaigns-ledger|
a2-manage-913-no-donations|admin2|GET|/app.php/donationcampaigns/topic/913|<dl class="details">;donationcampaigns-ledger|
a2-donation-add-912|admin2|GET|/app.php/donationcampaigns/campaign/912/donation/add|id="donationcampaigns_donation"|
a2-donation-add-912-error|admin2|POST|/app.php/donationcampaigns/campaign/912/donation/add|id="donationcampaigns_donation";class="errorbox"|donation_amount=abc&donation_time=2026-10-04&donor_name=Snapshot&submit=1
a2-donation-edit-955-public|admin2|GET|/app.php/donationcampaigns/donation/955/edit|id="donationcampaigns_donation"|
a2-donation-edit-953-anonymous|admin2|GET|/app.php/donationcampaigns/donation/953/edit|id="donationcampaigns_donation"|
acp-settings|acp|GET|/adm/index.php?ACP&mode=settings|id="donationcampaigns_settings"|
acp-settings-error|acp|POST|/adm/index.php?ACP&mode=settings|id="donationcampaigns_settings";class="errorbox"|donationcampaigns_currency_code=E1&donationcampaigns_currency_symbol=%E2%82%AC&donationcampaigns_currency_symbol_before=0&donationcampaigns_currency_symbol_space=1&donationcampaigns_currency_exponent=2&donationcampaigns_donor_list_limit=50&donationcampaigns_list_enabled=1&submit=1
acp-settings-error-confirm|acp|POST|/adm/index.php?ACP&mode=settings|id="donationcampaigns_settings";id="donationcampaigns_confirm_exponent"|donationcampaigns_currency_code=E1&donationcampaigns_currency_symbol=%E2%82%AC&donationcampaigns_currency_symbol_before=0&donationcampaigns_currency_symbol_space=1&donationcampaigns_currency_exponent=3&donationcampaigns_donor_list_limit=50&donationcampaigns_list_enabled=1&submit=1
acp-campaigns-page1|acp|GET|/adm/index.php?ACP&mode=campaigns|class="tabulated";mode=campaigns|
acp-campaigns-page2|acp|GET|/adm/index.php?ACP&mode=campaigns&start=25|class="tabulated";mode=campaigns|
acp-donations-912|acp|GET|/adm/index.php?ACP&mode=donations&campaign_id=912|class="tabulated";style="float:|
acp-donations-913-empty|acp|GET|/adm/index.php?ACP&mode=donations&campaign_id=913|class="tabulated";style="float:|
g-topic-918-dated-and-private|guest|GET|/viewtopic.php?t=918|class="panel donationcampaigns-panel";donationcampaigns-donors|
g-topic-919-and-others|guest|GET|/viewtopic.php?t=919|class="panel donationcampaigns-panel";donationcampaigns-donors|
c1-topic-918-tools-donations-only|cashier1|GET|/viewtopic.php?t=918|class="panel donationcampaigns-panel";donationcampaigns-manage|
c1-manage-918-donations-only|cashier1|GET|/app.php/donationcampaigns/topic/918|<dl class="details">;donationcampaigns-ledger|
c1-donation-add-918|cashier1|GET|/app.php/donationcampaigns/campaign/918/donation/add|id="donationcampaigns_donation"|
u1-campaign-create-seed-topic|user1|GET|/app.php/donationcampaigns/topic/{SEED_TOPIC_NO_CAMPAIGN}/create|id="donationcampaigns_campaign"|
a2-donation-edit-dated|admin2|GET|/app.php/donationcampaigns/donation/{SEED_DONATION_DATED}/edit|id="donationcampaigns_donation"|
acp-donations-919-page1|acp|GET|/adm/index.php?ACP&mode=donations&campaign_id=919|class="tabulated";style="float:|
acp-donations-919-page3|acp|GET|/adm/index.php?ACP&mode=donations&campaign_id=919&start=50|class="tabulated";style="float:|
'

# Captured with the currency symbol before the amount (see the header).
STATES_SYMBOL_BEFORE='
sb-topic-912|guest|GET|/viewtopic.php?t=912|class="panel donationcampaigns-panel"|
sb-list-page1|guest|GET|/app.php/donationcampaigns|class="table1 donationcampaigns-list"|
sb-campaign-edit-913|user1|GET|/app.php/donationcampaigns/campaign/913/edit|id="donationcampaigns_campaign"|
sb-campaign-create-seed-topic|user1|GET|/app.php/donationcampaigns/topic/{SEED_TOPIC_NO_CAMPAIGN}/create|id="donationcampaigns_campaign"|
sb-posting-902-panel|user1|GET|/posting.php?mode=post&f=902|id="donationcampaigns-panel"|
sb-donation-add-912|admin2|GET|/app.php/donationcampaigns/campaign/912/donation/add|id="donationcampaigns_donation"|
sb-acp-settings|acp|GET|/adm/index.php?ACP&mode=settings|id="donationcampaigns_settings"|
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
	local account="$1" jar="$2" page args=() problem
	curl -sS -c "$jar" -b "$jar" -o "$jar.login.html" "$BOARD_URL/ucp.php?mode=login"
	problem="$(board_error "$jar.login.html")"
	if [ -n "$problem" ]; then
		echo "render-snapshot: cannot log in as $account: $problem" >&2
		exit 1
	fi
	while IFS= read -r field; do args+=(--data-urlencode "$field"); done < <(hidden_fields 'mode=login' < "$jar.login.html")
	if [ "${#args[@]}" -eq 0 ]; then
		echo "render-snapshot: cannot log in as $account: the login page has no login form" >&2
		exit 1
	fi
	sleep 1
	page="$(curl -sS -c "$jar" -b "$jar" "${args[@]}" \
		--data-urlencode "username=$account" --data-urlencode "password=$RS_PASSWORD" \
		--data-urlencode 'login=Login' "$BOARD_URL/ucp.php?mode=login")"
	# Read the whole page first: grep -q would close the pipe early and, under
	# pipefail, turn curl's SIGPIPE into a false login failure.
	page="$(curl -sS -c "$jar" -b "$jar" "$BOARD_URL/index.php")"
	case "$page" in
		*mode=logout*) ;;
		*) echo "render-snapshot: login as $account failed" >&2; exit 1 ;;
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

phpbb_cli()
{
	docker compose -f "$COMPOSE_FILE" exec -T -u www-data web php /var/www/html/phpBB/bin/phpbbcli.php --no-ansi "$@"
}

# A phpBB maintenance or error page, named; nothing for any other page.
board_error()
{
	if grep -qF 'currently unavailable' "$1"; then echo 'phpBB maintenance page ("board is currently unavailable")'; return; fi
	if grep -qF 'Unable to write to the cache directory' "$1"; then echo 'phpBB cannot write its cache directory'; return; fi
	if grep -qE 'General Error|SQL ERROR|Fatal error|Twig\\Error|\[phpBB Debug\]' "$1"; then echo 'phpBB error page or debug notice'; return; fi
}

# Why a fetched page is not the page a state expects, or nothing if it is.
# $1 file, $2 HTTP status, $3 account, $4 markers ("a;b;c").
page_problem()
{
	local file="$1" status="$2" account="$3" markers="$4" marker error
	[ "$status" = 200 ] || { echo "HTTP $status"; return; }
	error="$(board_error "$file")"
	[ -z "$error" ] || { echo "$error"; return; }
	if grep -qE 'id="login"|name="credential"' "$file"; then echo 'a login form where none is expected'; return; fi
	if [ "$account" != guest ] && ! grep -qF 'mode=logout' "$file"; then echo "not logged in as $account any more"; return; fi
	while IFS= read -r marker; do
		[ -z "$marker" ] || grep -qF -- "$marker" "$file" || { echo "expected marker missing: $marker"; return; }
	done <<< "$(printf '%s\n' "$markers" | tr ';' '\n')"
}

# Capture every state of $1 into $2; the seed ids replace their placeholders.
capture_states()
{
	local states="$1" out="$2" seed_ids problem
	seed_ids="$("$(dirname "$0")/seed-board.sh" ids)"

	while IFS='|' read -r name account method path expect fields; do
		[ -n "$name" ] || continue
		local jar=() url status args=() page sid needle key value
		while IFS='=' read -r key value; do
			path="${path//\{$key\}/$value}"
		done <<< "$seed_ids"
		case "$path" in
			*'{SEED_'*) echo "FAIL $name: no seed id for $path; run seed-board.sh seed" >&2; return 1 ;;
		esac
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
		problem="$(page_problem "$tmp/page.html" "$status" "$account" "$expect")"
		if [ -n "$problem" ]; then
			# Kept for inspection under its own name; never saved as the state.
			cp "$tmp/page.html" "$out/FAILED-$name.raw.html"
			echo "FAIL $name ($method $path as $account): $problem" >&2
			echo "render-snapshot: capture stopped; $out is incomplete and must not be compared" >&2
			return 1
		fi
		{ echo "<!-- state: $name | $account | $method | $path | HTTP $status -->"; normalise < "$tmp/page.html"; } > "$out/$name.html"
		echo "ok   $name"
	done <<< "$states"
}

restore_symbol_setting()
{
	if [ -n "${symbol_before_was:-}" ]; then
		phpbb_cli config:set "$SYMBOL_BEFORE_KEY" "$symbol_before_was" > /dev/null
		echo "restored $SYMBOL_BEFORE_KEY=$symbol_before_was"
		symbol_before_was=''
	fi
}

capture()
{
	local out="$1"
	[ -n "${RS_PASSWORD:-}" ] || { echo "render-snapshot: RS_PASSWORD is not set" >&2; exit 64; }
	# Globals, not locals: the EXIT trap runs after this function has returned.
	tmp="$(mktemp -d)"
	symbol_before_was=''
	trap 'restore_symbol_setting; rm -rf "$tmp"' EXIT
	mkdir -p "$out"

	login user1 "$tmp/user1.jar"
	login cashier1 "$tmp/cashier1.jar"
	login admin2 "$tmp/admin2.jar"
	# A session of its own: the ACP re-authentication replaces the session it
	# runs in, so sharing admin2's would log the frontend states out.
	login admin2 "$tmp/acp.jar"
	acp_login "$tmp/acp.jar"

	capture_states "$STATES" "$out" || exit 1

	symbol_before_was="$(phpbb_cli config:get "$SYMBOL_BEFORE_KEY" | tr -d '[:space:]')"
	[ "$symbol_before_was" = 0 ] || { echo "render-snapshot: expected $SYMBOL_BEFORE_KEY=0, found '$symbol_before_was'" >&2; symbol_before_was=''; return 1; }
	phpbb_cli config:set "$SYMBOL_BEFORE_KEY" 1 > /dev/null
	echo "set $SYMBOL_BEFORE_KEY=1"
	capture_states "$STATES_SYMBOL_BEFORE" "$out" || exit 1
	restore_symbol_setting
}

case "${1:-}" in
	capture) [ "$#" -eq 2 ] || usage; capture "$2" ;;
	diff) [ "$#" -eq 3 ] || usage; diff -ru "$2" "$3" && echo "render-snapshot: identical" ;;
	*) usage ;;
esac
