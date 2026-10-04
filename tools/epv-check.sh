#!/usr/bin/env bash
#
# Run the phpBB Extension Pre Validator and fail on what its summary says.
#
# EPV exits 0 even when it prints "Validation: FAILED" (see 66f5f87), so its
# exit code cannot gate anything. This wrapper runs the EPV command given as
# arguments, shows its output unchanged, and then reads the summary line
#
#     Fatal: N, Error: N, Warning: N, Notice: N
#
# that EPV prints in both outcomes (phpbb/epv src/Tests/TestStartup.php):
#
#   exit 1  Fatal, Error or Warning > 0
#   exit 2  no summary line at all (EPV crashed or changed its format) —
#           never treated as a pass
#   exit 0  otherwise; notices are reported but do not fail
#
# Usage (from the repository root):
#   CI:           tools/epv-check.sh ./vendor/bin/EPV.php run --dir=ext
#   Docker board: tools/epv-check.sh docker compose -f Testboard_donation/docker-compose.yml \
#                     exec -T web /opt/epv/vendor/bin/EPV.php run --dir=/var/www/html/phpBB/ext
#
# Tests: tools/tests/epv-check.test.sh

set -uo pipefail

if [ "$#" -eq 0 ]; then
	echo "usage: $0 <EPV command and arguments>" >&2
	exit 64
fi

output="$("$@" 2>&1)"
printf '%s\n' "$output"

# Colour codes off, then the last summary line.
plain="$(printf '%s\n' "$output" | sed $'s/\033\\[[0-9;]*m//g')"
summary="$(printf '%s\n' "$plain" | grep -oE 'Fatal: [0-9]+, Error: [0-9]+, Warning: [0-9]+, Notice: [0-9]+' | tail -n 1)"

if [ -z "$summary" ]; then
	echo "epv-check: no EPV summary line found; treating as failure" >&2
	exit 2
fi

count() {
	printf '%s\n' "$summary" | sed -E "s/.*$1: ([0-9]+).*/\\1/"
}

fatal="$(count Fatal)"
error="$(count Error)"
warning="$(count Warning)"
notice="$(count Notice)"

if [ "$fatal" -gt 0 ] || [ "$error" -gt 0 ] || [ "$warning" -gt 0 ]; then
	echo "epv-check: FAILED — fatal $fatal, error $error, warning $warning, notice $notice" >&2
	exit 1
fi

if [ "$notice" -gt 0 ]; then
	echo "epv-check: passed with $notice notice(s) — review them" >&2
else
	echo "epv-check: passed — no issues"
fi

exit 0
