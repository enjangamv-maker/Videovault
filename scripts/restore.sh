#!/usr/bin/env bash
#
# VideoVault — Restore from Backup
# Usage: ./restore.sh <backup-file.tar.gz>
#
set -euo pipefail

APP_DIR="/var/www/html/35/percobaan/videovault"

if [[ $# -lt 1 ]]; then
    echo "Usage: $0 <backup-file.tar.gz>"
    echo "Contoh: $0 storage/backups/2026-10-12_02-00-01.tar.gz"
    exit 1
fi

ARCHIVE="$1"
if [[ ! -f "$ARCHIVE" ]]; then
    echo "❌ File tidak ditemukan: $ARCHIVE"
    exit 1
fi

echo "════════════════════════════════════════════"
echo "⚠️  RESTORE DARI BACKUP"
echo "════════════════════════════════════════════"
echo "  Archive: $ARCHIVE"
echo "  Target : $APP_DIR"
echo ""
echo "  ⚠️  Semua data di target akan DITIMPA!"
echo ""
read -p "  Ketik 'RESTORE' untuk konfirmasi: " CONFIRM

if [[ "$CONFIRM" != "RESTORE" ]]; then
    echo "❌ Dibatalkan."
    exit 1
fi

# ─── Extract ───
TMP_DIR=$(mktemp -d)
echo "📦 Extract..."
tar -xzf "$ARCHIVE" -C "$TMP_DIR"

# ─── Load .env ───
source <(grep -v '^#' "$APP_DIR/.env" | grep -v '^$')

# ─── Restore DB ───
if [[ -f "$TMP_DIR/database.sql" ]]; then
    echo "🗄️  Restore database..."
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$TMP_DIR/database.sql"
    echo "   ✓ Database restored"
fi

# ─── Restore Videos ───
if [[ -d "$TMP_DIR/uploads/videos" ]]; then
    echo "🎥 Restore videos..."
    rsync -a --delete "$TMP_DIR/uploads/videos/" "$APP_DIR/public/uploads/videos/"
    echo "   ✓ Videos restored"
fi

# ─── Restore Thumbnails ───
if [[ -d "$TMP_DIR/uploads/thumbnails" ]]; then
    echo "🖼️  Restore thumbnails..."
    rsync -a --delete "$TMP_DIR/uploads/thumbnails/" "$APP_DIR/public/uploads/thumbnails/"
    echo "   ✓ Thumbnails restored"
fi

# ─── Fix ownership ───
chown -R www-data:www-data "$APP_DIR/public/uploads"
chmod -R 775 "$APP_DIR/public/uploads"

rm -rf "$TMP_DIR"

echo ""
echo "✅ Restore selesai."
echo "   Restart Apache: sudo systemctl restart apache2"
