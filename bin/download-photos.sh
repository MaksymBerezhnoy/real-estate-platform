#!/usr/bin/env bash
#
# Downloads the photo set listed in data/photos.json into data/photos/
# and verifies every file against its SHA-256 checksum.
#
# Usage: bin/download-photos.sh
# Requires: curl, jq, and shasum or sha256sum.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MANIFEST="$ROOT_DIR/data/photos.json"
TARGET_DIR="$ROOT_DIR/data/photos"
USER_AGENT="Mozilla/5.0 (compatible; photo-set-downloader)"

for tool in curl jq; do
	if ! command -v "$tool" > /dev/null; then
		echo "Missing required tool: $tool" >&2
		exit 1
	fi
done

checksum() {
	if command -v sha256sum > /dev/null; then
		sha256sum "$1" | cut -d ' ' -f 1
	else
		shasum -a 256 "$1" | cut -d ' ' -f 1
	fi
}

mkdir -p "$TARGET_DIR"

downloaded=0
skipped=0
failed=0

while IFS=$'\t' read -r file url expected; do
	path="$TARGET_DIR/$file"

	if [ -f "$path" ] && [ "$(checksum "$path")" = "$expected" ]; then
		skipped=$((skipped + 1))
		continue
	fi

	if ! curl -fsSL -A "$USER_AGENT" --max-time 120 -o "$path.part" "$url"; then
		echo "FAIL      $file (download error)" >&2
		rm -f "$path.part"
		failed=$((failed + 1))
		continue
	fi

	if [ "$(checksum "$path.part")" != "$expected" ]; then
		echo "FAIL      $file (checksum mismatch, the source file has changed)" >&2
		rm -f "$path.part"
		failed=$((failed + 1))
		continue
	fi

	mv "$path.part" "$path"
	echo "OK        $file"
	downloaded=$((downloaded + 1))
done < <(jq -r '.photos[] | [.file, .download_url, .sha256] | @tsv' "$MANIFEST")

echo "Downloaded: $downloaded, already present: $skipped, failed: $failed"

if [ "$failed" -gt 0 ]; then
	exit 1
fi
