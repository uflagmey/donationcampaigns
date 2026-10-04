#!/usr/bin/env bash
#
# Add to the local Docker board the data the beta4 render snapshot needs and
# the board did not have (1.0.0-beta4). Idempotent: every step looks for its
# own result first, so a second run changes nothing.
#
# Development tool, NOT shipped (it lives outside ext/). Back up the board's
# database before the first run.
#
# Usage (from the repository root, board running):
#   RS_PASSWORD=… tools/beta4/seed-board.sh seed
#   tools/beta4/seed-board.sh ids      # KEY=VALUE lines for render-snapshot.sh
#
# Environment:
#   BOARD_URL     default http://localhost:8081
#   COMPOSE_FILE  default Testboard_donation/docker-compose.yml
#   RS_PASSWORD   password of the board's test accounts; also given to the
#                 account created here. Required for seed.
#
# What it adds, and the template state each one makes reachable:
#   cashier1   a registered account with f_donationcampaigns_donations in
#              forum 902 and nothing else: the manage landing with the ledger
#              but without the campaign buttons, the donation form.
#   topic      "Seed: a topic without a campaign" in forum 902, posted by user1
#              (who may manage campaigns there): the create form.
#   campaign 918  a public and a private donation; the campaign shows dates:
#              a donor line with its date, and the anonymous line.
#   campaign 919  53 public donations against a donor list limit of 50:
#              "and 3 others"; also a three-page ACP donations list.
#
# The account and its permission use phpBB's own API (user_add(),
# auth_admin::acl_set()), as the board's other test accounts did. The topic
# and the donations go through the board's forms, so they are written by the
# same code a user's would be. Nothing is written with hand-made SQL.
#
# The currency symbol before the amount is not seeded: it is a board-wide
# setting, and fixing it would remove the default "after" state. The snapshot
# captures that state by switching the setting for its duration.

set -euo pipefail

BOARD_URL="${BOARD_URL:-http://localhost:8081}"
COMPOSE_FILE="${COMPOSE_FILE:-Testboard_donation/docker-compose.yml}"

TOPIC_TITLE='Seed: a topic without a campaign'
DATED_CAMPAIGN=918
MANY_CAMPAIGN=919
MANY_COUNT=53

usage()
{
	sed -n '2,/^set -euo/p' "$0" | sed '$d' >&2
	exit 64
}

# Run PHP inside the board with phpBB booted (common.php), script on stdin.
# As www-data, like phpBB's CLI per the board's README: booting phpBB as root
# can leave cache files owned by root, and the board then cannot write them.
board_php()
{
	docker compose -f "$COMPOSE_FILE" exec -T -u www-data "$@" web sh -c 'cd /var/www/html/phpBB && php'
}

# One value from the board, by a fixed lookup name.
lookup()
{
	board_php -e "LOOKUP=$1" -e "TOPIC_TITLE=$TOPIC_TITLE" -e "DATED_CAMPAIGN=$DATED_CAMPAIGN" -e "MANY_CAMPAIGN=$MANY_CAMPAIGN" <<'PHP'
<?php
define('IN_PHPBB', true);
$phpbb_root_path = './';
$phpEx = 'php';
include 'common.php';

switch (getenv('LOOKUP'))
{
	case 'topic':
		$sql = 'SELECT t.topic_id FROM ' . TOPICS_TABLE . ' t
			WHERE t.forum_id = 902
				AND t.topic_title = \'' . $db->sql_escape(utf8_htmlspecialchars(getenv('TOPIC_TITLE'))) . '\'
			ORDER BY t.topic_id';
	break;

	case 'dated':
		$sql = 'SELECT donation_id FROM ' . $table_prefix . 'ufdc_donations
			WHERE campaign_id = ' . (int) getenv('DATED_CAMPAIGN') . "
				AND donor_name = 'Seed donor (dated)'";
	break;

	case 'private':
		$sql = 'SELECT donation_id FROM ' . $table_prefix . 'ufdc_donations
			WHERE campaign_id = ' . (int) getenv('DATED_CAMPAIGN') . "
				AND donor_name = 'Seed donor (private)'";
	break;

	case 'many':
		$sql = 'SELECT donor_name FROM ' . $table_prefix . 'ufdc_donations
			WHERE campaign_id = ' . (int) getenv('MANY_CAMPAIGN') . "
				AND donor_name LIKE 'Seed donor %'
				AND donor_name NOT IN ('Seed donor (dated)', 'Seed donor (private)')";
	break;

	case 'cashier':
		$sql = 'SELECT user_id FROM ' . USERS_TABLE . " WHERE username_clean = 'cashier1'";
	break;

	default:
		exit(2);
}

$result = $db->sql_query($sql);
while ($row = $db->sql_fetchrow($result))
{
	echo reset($row), "\n";
}
$db->sql_freeresult($result);
PHP
}

ensure_cashier()
{
	board_php -e "PASSWORD=$RS_PASSWORD" <<'PHP'
<?php
define('IN_PHPBB', true);
$phpbb_root_path = './';
$phpEx = 'php';
include 'common.php';
include $phpbb_root_path . 'includes/functions_user.php';
include $phpbb_root_path . 'includes/acp/auth.php';

$user_id = (int) $db->sql_fetchfield('user_id', false, $db->sql_query('SELECT user_id FROM ' . USERS_TABLE . " WHERE username_clean = 'cashier1'"));

if (!$user_id)
{
	$user_id = user_add(array(
		'username'		=> 'cashier1',
		'user_password'	=> $phpbb_container->get('passwords.manager')->hash(getenv('PASSWORD')),
		'user_email'	=> 'cashier1@example.com',
		'group_id'		=> (int) $db->sql_fetchfield('group_id', false, $db->sql_query('SELECT group_id FROM ' . GROUPS_TABLE . " WHERE group_name = 'REGISTERED'")),
		'user_type'		=> USER_NORMAL,
		'user_lang'		=> 'en',
		'user_timezone'	=> 'UTC',
		'user_regdate'	=> time(),
	));
	echo "created cashier1 (user $user_id)\n";
}
else
{
	echo "cashier1 exists (user $user_id)\n";
}

$row = $db->sql_fetchrow($db->sql_query('SELECT * FROM ' . USERS_TABLE . ' WHERE user_id = ' . $user_id));
$check = new \phpbb\auth\auth();
$check->acl($row);

if (!$check->acl_get('f_donationcampaigns_donations', 902))
{
	(new auth_admin())->acl_set('user', 902, $user_id, array('f_donationcampaigns_donations' => ACL_YES));
	echo "granted f_donationcampaigns_donations in forum 902\n";
}
else
{
	echo "f_donationcampaigns_donations in forum 902 already granted\n";
}
PHP
}

# Hidden form keys of the form whose action contains $1, as name=value lines.
form_keys()
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
				$value =~ s/&amp;/&/g;
				print "$name=$value\n" if defined $name && $name =~ /^(form_token|creation_time|lastclick|sid)$/;
			}
			last;
		}
	' "$1"
}

login()
{
	local account="$1" jar="$2" page args=()
	page="$(curl -sS -c "$jar" -b "$jar" "$BOARD_URL/ucp.php?mode=login")"
	while IFS= read -r field; do args+=(--data-urlencode "$field"); done \
		< <(printf '%s' "$page" | perl -0777 -ne 'if (/<form[^>]*mode=login.*?<\/form>/s) { my $f = $&; while ($f =~ /<input type="hidden" name="([^"]*)" value="([^"]*)"/g) { my ($n, $v) = ($1, $2); $v =~ s/&amp;/&/g; print "$n=$v\n" } }')
	# check_form_key() refuses a form posted in the second it was created.
	sleep 1
	curl -sS -c "$jar" -b "$jar" "${args[@]}" --data-urlencode "username=$account" \
		--data-urlencode "password=$RS_PASSWORD" --data-urlencode 'login=Login' \
		"$BOARD_URL/ucp.php?mode=login" > /dev/null
	page="$(curl -sS -c "$jar" -b "$jar" "$BOARD_URL/index.php")"
	case "$page" in
		*mode=logout*) ;;
		*) echo "seed-board: login as $account failed" >&2; exit 1 ;;
	esac
}

# Fetch a form page, then post the given fields with its form keys.
post_form()
{
	local jar="$1" url="$2" needle="$3" page args=()
	shift 3
	page="$(curl -sS -c "$jar" -b "$jar" "$url")"
	while IFS= read -r field; do args+=(--data-urlencode "$field"); done < <(printf '%s' "$page" | form_keys "$needle")
	for field in "$@"; do args+=(--data-urlencode "$field"); done
	sleep 1
	curl -sS -o /dev/null -w '%{http_code}' -c "$jar" -b "$jar" "${args[@]}" "$url"
}

ensure_topic()
{
	local jar="$1" status
	if [ -n "$(lookup topic)" ]; then
		echo "topic exists ($(lookup topic | head -n 1))"
		return
	fi
	status="$(post_form "$jar" "$BOARD_URL/posting.php?mode=post&f=902" 'posting.php' \
		"subject=$TOPIC_TITLE" \
		'message=A topic without a donation campaign, for the render snapshot of the create form.' \
		'post=Submit')"
	[ -n "$(lookup topic)" ] || { echo "seed-board: topic not created (HTTP $status)" >&2; exit 1; }
	echo "created topic $(lookup topic | head -n 1)"
}

add_donation()
{
	local jar="$1" campaign="$2" name="$3" amount="$4" date="$5" public="$6" fields=()
	fields=("donation_amount=$amount" "donation_time=$date" "donor_name=$name" 'submit=1')
	[ "$public" = 1 ] && fields+=('donation_public=1')
	post_form "$jar" "$BOARD_URL/app.php/donationcampaigns/campaign/$campaign/donation/add" 'donation/add' "${fields[@]}" > /dev/null
}

ensure_dated_donations()
{
	local jar="$1"
	if [ -z "$(lookup dated)" ]; then
		add_donation "$jar" "$DATED_CAMPAIGN" 'Seed donor (dated)' '12,34' '2026-09-01' 1
		[ -n "$(lookup dated)" ] || { echo "seed-board: dated donation not created" >&2; exit 1; }
		echo "created the dated donation in campaign $DATED_CAMPAIGN"
	else
		echo "dated donation exists"
	fi
	if [ -z "$(lookup private)" ]; then
		add_donation "$jar" "$DATED_CAMPAIGN" 'Seed donor (private)' '5,00' '2026-09-02' 0
		[ -n "$(lookup private)" ] || { echo "seed-board: private donation not created" >&2; exit 1; }
		echo "created the private donation in campaign $DATED_CAMPAIGN"
	else
		echo "private donation exists"
	fi
}

ensure_many_donations()
{
	local jar="$1" existing added=0 i name
	existing="$(lookup many)"
	for i in $(seq -w 1 "$MANY_COUNT"); do
		name="Seed donor $i"
		if ! printf '%s\n' "$existing" | grep -qxF "$name"; then
			add_donation "$jar" "$MANY_CAMPAIGN" "$name" '1,00' '2026-09-03' 1
			added=$((added + 1))
		fi
	done
	[ "$(lookup many | wc -l | tr -d ' ')" = "$MANY_COUNT" ] \
		|| { echo "seed-board: campaign $MANY_CAMPAIGN does not hold $MANY_COUNT seed donations" >&2; exit 1; }
	echo "campaign $MANY_CAMPAIGN: $MANY_COUNT seed donations ($added added)"
}

seed()
{
	[ -n "${RS_PASSWORD:-}" ] || { echo "seed-board: RS_PASSWORD is not set" >&2; exit 64; }
	tmp="$(mktemp -d)"
	trap 'rm -rf "$tmp"' EXIT

	ensure_cashier
	login user1 "$tmp/user1.jar"
	ensure_topic "$tmp/user1.jar"
	login admin2 "$tmp/admin2.jar"
	ensure_dated_donations "$tmp/admin2.jar"
	ensure_many_donations "$tmp/admin2.jar"
}

ids()
{
	echo "SEED_TOPIC_NO_CAMPAIGN=$(lookup topic | head -n 1)"
	echo "SEED_DONATION_DATED=$(lookup dated | head -n 1)"
	echo "SEED_DONATION_PRIVATE=$(lookup private | head -n 1)"
}

case "${1:-}" in
	seed) seed ;;
	ids) ids ;;
	*) usage ;;
esac
