#!/bin/bash
# ONLY deploy command for Hostinger — preserves profile photos & uploaded images.
exec bash "$(dirname "$0")/deploy-hostinger.sh" "$@"
