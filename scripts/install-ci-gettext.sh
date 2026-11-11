#!/usr/bin/env bash
# Keep translation checks mandatory even when the runner's Ubuntu mirror fails.
set -euo pipefail

has_gettext() {
  local tool
  for tool in msgmerge msgcmp msgfmt msgattrib; do
    command -v "$tool" >/dev/null || return 1
  done
}
if has_gettext; then
  echo 'GNU gettext tools are already available.'
  exit 0
fi

apt_options=(-o Acquire::Retries=2 -o Acquire::http::Timeout=20 -o Acquire::https::Timeout=20 -o APT::Update::Error-Mode=any)
if sudo apt-get "${apt_options[@]}" update &&
   sudo apt-get "${apt_options[@]}" install -y --no-install-recommends gettext; then
  has_gettext
  exit 0
fi

echo '::warning::Runner package sources failed; retrying with direct Ubuntu HTTPS repositories.'
. /etc/os-release
[[ "$ID" == ubuntu && "${VERSION_CODENAME:-}" =~ ^[a-z]+$ ]]
case "$(dpkg --print-architecture)" in
  amd64|i386) archive=https://archive.ubuntu.com/ubuntu; security=https://security.ubuntu.com/ubuntu ;;
  *) archive=https://ports.ubuntu.com/ubuntu-ports; security=$archive ;;
esac

# Use isolated sources and indexes so APT cannot reuse a failed mirror's URLs.
# Do not alter the runner's sources used by later workflow steps.
apt_temp=$(mktemp -d)
trap 'sudo rm -rf -- "$apt_temp"' EXIT
chmod 755 "$apt_temp"
cat > "$apt_temp/sources.list" <<EOF
deb $archive $VERSION_CODENAME main
deb $archive $VERSION_CODENAME-updates main
deb $security $VERSION_CODENAME-security main
EOF
sudo mkdir -p "$apt_temp/lists/partial"
apt_options+=(-o "Dir::Etc::sourcelist=$apt_temp/sources.list" -o Dir::Etc::sourceparts=- -o "Dir::State::lists=$apt_temp/lists")
sudo apt-get "${apt_options[@]}" update
sudo apt-get "${apt_options[@]}" install -y --no-install-recommends gettext
has_gettext
