#!/usr/bin/env bash
# Configure every subsequent APT consumer in this disposable CI job, including
# Playwright's own install-deps subprocess and the WordPress SVN test setup.
set -euo pipefail
. /etc/os-release
[[ "$ID" == ubuntu && "${VERSION_CODENAME:-}" =~ ^[a-z]+$ ]]
case "$(dpkg --print-architecture)" in
  amd64|i386) mirrors=(https://mirrors.edge.kernel.org/ubuntu https://archive.ubuntu.com/ubuntu) ;;
  *) mirrors=(https://ports.ubuntu.com/ubuntu-ports) ;;
esac
work=$(mktemp -d)
trap 'sudo rm -rf -- "$work"' EXIT
chmod 755 "$work"
for archive in "${mirrors[@]}"; do
  cat > "$work/sources.list" <<EOF
deb [signed-by=/usr/share/keyrings/ubuntu-archive-keyring.gpg] $archive $VERSION_CODENAME main restricted universe multiverse
deb [signed-by=/usr/share/keyrings/ubuntu-archive-keyring.gpg] $archive $VERSION_CODENAME-updates main restricted universe multiverse
deb [signed-by=/usr/share/keyrings/ubuntu-archive-keyring.gpg] $archive $VERSION_CODENAME-security main restricted universe multiverse
EOF
  # APT reads this configuration even when Playwright invokes it through sudo.
  # Isolated indexes exclude old Azure URLs and unrelated third-party repositories.
  sudo install -m 644 "$work/sources.list" /etc/apt/farn-ci.list
  cat > "$work/99-farn-ci" <<'EOF'
Dir::Etc::sourcelist "/etc/apt/farn-ci.list";
Dir::Etc::sourceparts "-";
Dir::State::lists "/var/lib/apt/lists-farn-ci";
Acquire::Retries "0";
Acquire::http::Timeout "15";
Acquire::https::Timeout "15";
Acquire::Languages "none";
APT::Update::Error-Mode "any";
EOF
  sudo install -m 644 "$work/99-farn-ci" /etc/apt/apt.conf.d/99-farn-ci
  sudo mkdir -p /var/lib/apt/lists-farn-ci/partial
  if sudo timeout --kill-after=5s 90s apt-get update; then
    echo "All subsequent APT installs in this job use $archive."
    exit 0
  fi
  echo "::warning::APT metadata from $archive failed or timed out; trying the next mirror."
done
echo '::error::No Ubuntu mirror could be initialized; dependency installation cannot continue.'
exit 1
