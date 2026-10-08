#!/usr/bin/env bash
cd "$(dirname "$0")"
echo "CyberShield starting at http://localhost:8000"
php -S localhost:8000 -t .
