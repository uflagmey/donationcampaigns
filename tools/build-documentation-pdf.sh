#!/usr/bin/env bash
#
# Build the PDFs that accompany each release:
#
#   documentation  DonationCampaigns-Documentation.pdf   ("Complete Documentation")
#   manual-de      DonationCampaigns-Benutzerhandbuch.pdf (German user manual)
#   manual-en      DonationCampaigns-User-Manual.pdf      (English user manual)
#   all            all three (default)
#
# Pipeline (the same one that produced the beta1 PDFs):
#
#   Markdown sources  --pandoc-->  one standalone HTML  --Chrome-->  PDF
#
# pandoc turns the Markdown into a single HTML file: the title block becomes
# the cover page and --toc becomes the Contents page. Chrome (headless) prints
# that HTML to PDF, so the fonts and layout match a normal "Print to PDF" from
# the browser. Styling lives in documentation.css; the manuals add
# manual.css on top (amber call-out boxes, as in the beta1 manuals).
#
# Sources:
#   documentation  the five shipped Markdown documents of the extension
#   manual-*       docs/manual/*.md in the repository root (not shipped in
#                  the extension package; the PDFs are release assets)
#
# Requirements: pandoc, and Google Chrome (macOS default install path, or
# set CHROME to any Chrome/Chromium binary).
#
# Usage: tools/build-documentation-pdf.sh [documentation|manual-de|manual-en|all]
#
# Output goes to the repository root under the fixed release file names.

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ext="$repo_root/ext/uflagmey/donationcampaigns"
manual="$repo_root/docs/manual"
target="${1:-all}"

# Version from the shipped manifest; date is the build date.
version="$(sed -n 's/.*"version": *"\([^"]*\)".*/\1/p' "$ext/composer.json" | head -n1)"
date_str="$(date +%Y-%m-%d)"

pandoc="${PANDOC:-pandoc}"
command -v "$pandoc" >/dev/null 2>&1 || pandoc="/opt/anaconda3/bin/pandoc"
command -v "$pandoc" >/dev/null 2>&1 || { echo "pandoc not found" >&2; exit 1; }

chrome="${CHROME:-/Applications/Google Chrome.app/Contents/MacOS/Google Chrome}"
[ -x "$chrome" ] || { echo "Google Chrome not found at: $chrome" >&2; exit 1; }

work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT

# build <output.pdf> <lang> <title> <subtitle> <cover line> <toc title> <toc depth> <extra css|""> <source>...
build() {
	local out="$1" lang="$2" title="$3" subtitle="$4" cover="$5" toc_title="$6" toc_depth="$7" extra_css="$8"
	shift 8
	local css=(--css "$repo_root/tools/documentation.css")
	if [ -n "$extra_css" ]; then css+=(--css "$extra_css"); fi
	local name html profile
	name="$(basename "$out" .pdf)"
	html="$work/$name.html"
	profile="$work/chrome-profile-$name"

	echo "pandoc: assembling $name (version $version)"
	# --resource-path lets the manuals reference ../images/ relative to
	# their own folder; --self-contained then embeds the images.
	"$pandoc" "$@" \
		--from gfm \
		--to html5 \
		--standalone \
		--self-contained \
		--resource-path=".:$(dirname "$1")" \
		--toc \
		--toc-depth="$toc_depth" \
		--metadata lang="$lang" \
		--metadata title="$title" \
		--metadata subtitle="$subtitle" \
		--metadata date="$cover" \
		--metadata toc-title="$toc_title" \
		"${css[@]}" \
		--output "$html"

	echo "chrome: printing to PDF -> $out"
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
}

build_documentation() {
	# Sources, in reading order. Each file's H1 becomes a top-level TOC entry.
	build "$repo_root/DonationCampaigns-Documentation.pdf" en \
		"Donation Campaigns" \
		"phpBB Extension — Complete Documentation" \
		"Version $version · $date_str" \
		"Contents" 2 "" \
		"$ext/README.md" \
		"$ext/docs/ADMIN_GUIDE.md" \
		"$ext/docs/PRIVACY.md" \
		"$ext/RELEASE_NOTES_BETA2.md" \
		"$ext/docs/DEVELOPERS.md"
}

build_manual_de() {
	build "$repo_root/DonationCampaigns-Benutzerhandbuch.pdf" de \
		"Spendenkampagnen" \
		"Benutzerhandbuch — Einrichtung und Verwendung der Erweiterung" \
		"Version $version" \
		"Inhalt" 2 "$repo_root/tools/manual.css" \
		"$manual/benutzerhandbuch.md"
}

build_manual_en() {
	build "$repo_root/DonationCampaigns-User-Manual.pdf" en \
		"Donation Campaigns" \
		"User Manual — configuring and using the extension" \
		"Version $version" \
		"Contents" 2 "$repo_root/tools/manual.css" \
		"$manual/user-manual.md"
}

case "$target" in
	documentation) build_documentation ;;
	manual-de)     build_manual_de ;;
	manual-en)     build_manual_en ;;
	all)           build_documentation; build_manual_de; build_manual_en ;;
	*)
		echo "Usage: $0 [documentation|manual-de|manual-en|all]" >&2
		exit 2
		;;
esac
