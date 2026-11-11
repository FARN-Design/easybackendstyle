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

# Avoid the runner's mirror+file list: its DNS retries are not bounded by
# Acquire::http::Timeout and can consume the entire workflow step.
. /etc/os-release
[[ "$ID" == ubuntu && "${VERSION_CODENAME:-}" =~ ^[a-z]+$ ]]
case "$(dpkg --print-architecture)" in
  amd64|i386) mirrors=(https://mirrors.edge.kernel.org/ubuntu https://archive.ubuntu.com/ubuntu) ;;
  *) mirrors=(https://ports.ubuntu.com/ubuntu-ports) ;;
esac
apt_temp=$(mktemp -d)
trap 'sudo rm -rf -- "$apt_temp"' EXIT
chmod 755 "$apt_temp"

attempt=0
for archive in "${mirrors[@]}"; do
  attempt=$((attempt + 1))
  echo "Installing gettext from $archive (attempt $attempt/${#mirrors[@]})"
  # A separate index directory prevents stale package URLs leaking between mirrors.
  cat > "$apt_temp/sources.list" <<EOF
deb [signed-by=/usr/share/keyrings/ubuntu-archive-keyring.gpg] $archive $VERSION_CODENAME main
deb [signed-by=/usr/share/keyrings/ubuntu-archive-keyring.gpg] $archive $VERSION_CODENAME-updates main
deb [signed-by=/usr/share/keyrings/ubuntu-archive-keyring.gpg] $archive $VERSION_CODENAME-security main
EOF
  sudo mkdir -p "$apt_temp/lists-$attempt/partial"
  apt_options=(-o Acquire::Retries=0 -o Acquire::http::Timeout=15 -o Acquire::https::Timeout=15
    -o APT::Update::Error-Mode=any -o Acquire::Languages=none
    -o "Dir::Etc::sourcelist=$apt_temp/sources.list" -o Dir::Etc::sourceparts=-
    -o "Dir::State::lists=$apt_temp/lists-$attempt")
  # timeout also bounds DNS resolution. Never interrupt dpkg: download first,
  # then install offline only after every package has arrived successfully.
  if sudo timeout --kill-after=5s 60s apt-get "${apt_options[@]}" update &&
     sudo timeout --kill-after=5s 45s apt-get "${apt_options[@]}" install -y --no-install-recommends --download-only gettext; then
    sudo apt-get "${apt_options[@]}" install -y --no-install-recommends --no-download gettext
    has_gettext
    exit 0
  fi
  echo "::warning::Package download from $archive failed or timed out."
done

echo '::error::GNU gettext could not be installed from the available mirrors. Translation validation cannot continue.'
exit 1
