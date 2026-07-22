#!/usr/bin/env bash
#
# Build DonationCampaigns-Documentation.pdf — the bundled "Complete
# Documentation" that ships with each release.
#
# Pipeline (the same one that produced the beta1 PDF):
#
#   Markdown sources  --pandoc-->  one standalone HTML  --Chrome-->  PDF
#
# pandoc turns the five Markdown documents into a single HTML file: the
# title block becomes the cover page and --toc becomes the Contents page.
# Chrome (headless) prints that HTML to PDF, so the fonts and layout match a
# normal "Print to PDF" from the browser. Styling lives in documentation.css.
#
# The two user manuals (EN + DE) are authored separately and are NOT built
# here.
#
# Requirements: pandoc, and Google Chrome (macOS default install path).
#
# Usage: tools/build-documentation-pdf.sh [output.pdf]

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ext="$repo_root/ext/uflagmey/donationcampaigns"
out="${1:-$repo_root/DonationCampaigns-Documentation.pdf}"

# Sources, in reading order. Each file's H1 becomes a top-level TOC entry.
sources=(
	"$ext/README.md"
	"$ext/docs/ADMIN_GUIDE.md"
	"$ext/docs/PRIVACY.md"
	"$ext/RELEASE_NOTES_BETA1.md"
	"$ext/docs/DEVELOPERS.md"
)

# Version from the shipped manifest; date is the build date, as on the cover.
version="$(sed -n 's/.*"version": *"\([^"]*\)".*/\1/p' "$ext/composer.json" | head -n1)"
date_str="$(date +%Y-%m-%d)"

pandoc="${PANDOC:-pandoc}"
command -v "$pandoc" >/dev/null 2>&1 || pandoc="/opt/anaconda3/bin/pandoc"

chrome="${CHROME:-/Applications/Google Chrome.app/Contents/MacOS/Google Chrome}"
[ -x "$chrome" ] || { echo "Google Chrome not found at: $chrome" >&2; exit 1; }

work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
html="$work/documentation.html"

echo "pandoc: assembling HTML (version $version, $date_str)"
"$pandoc" "${sources[@]}" \
	--from gfm \
	--to html5 \
	--standalone \
	--self-contained \
	--toc \
	--toc-depth=2 \
	--metadata title="Donation Campaigns" \
	--metadata subtitle="phpBB Extension — Complete Documentation" \
	--metadata date="Version $version · $date_str" \
	--metadata toc-title="Contents" \
	--css "$repo_root/tools/documentation.css" \
	--output "$html"

echo "chrome: printing to PDF -> $out"
profile="$work/chrome-profile"
# Legacy --headless prints and exits reliably; --headless=new can hang.
# A throwaway --user-data-dir keeps this independent of a running Chrome.
"$chrome" \
	--headless \
	--disable-gpu \
	--no-sandbox \
	--no-first-run \
	--disable-crashpad \
	--disable-dev-shm-usage \
	--no-pdf-header-footer \
	--user-data-dir="$profile" \
	--run-all-compositor-stages-before-draw \
	--virtual-time-budget=15000 \
	--print-to-pdf="$out" \
	"file://$html" 2>/dev/null

echo "done: $out"
