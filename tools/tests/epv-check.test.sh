#!/usr/bin/env bash
#
# Tests for tools/epv-check.sh. Each case feeds a recorded EPV summary
# through the script (printf stands in for the EPV command) and checks the
# exit code. Run from anywhere; CI runs it before EPV itself.

set -uo pipefail

script="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/epv-check.sh"
esc=$'\033'
failures=0

check() {
	local expected="$1" name="$2" output="$3"
	"$script" printf '%s\n' "$output" >/dev/null 2>&1
	local actual=$?
	if [ "$actual" -eq "$expected" ]; then
		echo "ok   $name"
	else
		echo "FAIL $name: expected exit $expected, got $actual"
		failures=$((failures + 1))
	fi
}

check 0 "passed, all zero" \
	"PASSED:  Fatal: 0, Error: 0, Warning: 0, Notice: 0 "
check 0 "passed with ANSI colours" \
	"${esc}[30;42mPASSED:  Fatal: 0, Error: 0, Warning: 0, Notice: 0 ${esc}[39;49m"
check 0 "notices only do not fail" \
	"PASSED:  Fatal: 0, Error: 0, Warning: 0, Notice: 3 "
check 1 "one warning fails" \
	" Validation: FAILED
 Fatal: 0, Error: 0, Warning: 1, Notice: 0 "
check 1 "one error fails" \
	" Fatal: 0, Error: 1, Warning: 0, Notice: 2 "
check 1 "one fatal fails" \
	" Fatal: 1, Error: 0, Warning: 0, Notice: 0 "
check 1 "failed banner with ANSI colours" \
	"${esc}[37;41m Validation: FAILED ${esc}[39;49m
${esc}[37;41m Fatal: 0, Error: 0, Warning: 2, Notice: 0 ${esc}[39;49m"
check 2 "no summary line is never a pass" \
	"PHP Fatal error: Uncaught Exception in EPV.php"
check 2 "empty output is never a pass" \
	""

# The EPV command's own exit code is not trusted, but a failure of the
# command itself must not be hidden either.
"$script" sh -c 'echo "PASSED:  Fatal: 0, Error: 0, Warning: 0, Notice: 0 "; exit 0' >/dev/null 2>&1 \
	&& echo "ok   command exit 0 with a clean summary" \
	|| { echo "FAIL command exit 0 with a clean summary"; failures=$((failures + 1)); }

if [ "$failures" -gt 0 ]; then
	echo "$failures case(s) failed"
	exit 1
fi
echo "all cases passed"
