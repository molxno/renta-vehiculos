#!/usr/bin/env bash
set -euo pipefail

exec copilot \
  --allow-all \
  --max-autopilot-continues 20 \
  '--deny-tool=shell(git push --force)' \
  '--deny-tool=shell(gh pr merge)' \
  '--deny-tool=shell(git push origin main)' \
  '--deny-tool=shell(git push origin qa)' \
  '--deny-tool=shell(git push origin dev)' \
  "$@"
