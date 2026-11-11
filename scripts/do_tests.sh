#!/usr/bin/env bash
set -euo pipefail
python3 scripts/build-admin-colors.py --check
python3 tests/test_admin_colors.py
python3 tests/release/test_release.py
php tests/test_admin_color_runtime.php
php tests/test_settings_requests.php
php tests/test_initial_colors.php
vendor/bin/phpunit
npm test
