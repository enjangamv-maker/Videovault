#!/usr/bin/env bash
#
# VideoVault — Automated Backup
# Backup: database + videos + thumbnails
# Output: storage/backups/YYYY-MM-DD_HH-MM-SS.tar.gz
#
set -euo pipefail

# ─── Config ───
APP_DIR="/var/www/html/35/percobaan/videovault"
BACKUP_DIR="$APP_DIR/storage/backups"
LOG_FILE="$APP_DIR/storage/logs/backup.log"
RETENTION_DAYS=30

# Load .env
if [[ -f "$APP_DIR/.env" ]]; then
    set -a
    source <(grep -v '^#' "$APP_DIR/.env" | grep -v '^$')
    set +a
fi

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_NAME="${DB_NAME:-videovault}"
DB_USER="${DB_USER:-videovault_user}"
DB_PASS="${DB_PASS:-}"

# ─── Helpers ───
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

fail() {
    log "❌ ERROR: $1"
    exit 1
}

# ─── Start ───
TIMESTAMP=$(date '+%Y-%m-%d_%H-%M-%S')
TMP_DIR=$(mktemp -d)
ARCHIVE="$BACKUP_DIR/${TIMESTAMP}.tar.gz"

mkdir -p "$BACKUP_DIR"
log "════════════════════════════════════════════"
log "🚀 Backup started: $TIMESTAMP"
log "════════════════════════════════════════════"

# ─── 1. Backup Database ───
log "📦 [1/4] Backup database..."
mysqldump \
    --host="$DB_HOST" \
    --user="$DB_USER" \
    --password="$DB_PASS" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --routines \
    --triggers \
    --events \
    --default-character-set=utf8mb4 \
    "$DB_NAME" > "$TMP_DIR/database.sql" 2>/dev/null \
    || fail "mysqldump gagal"

DB_SIZE=$(du -h "$TMP_DIR/database.sql" | cut -f1)
log "   ✓ Database: $DB_SIZE"

# ─── 2. Backup Videos ───
log "🎥 [2/4] Backup videos..."
if [[ -d "$APP_DIR/public/uploads/videos" ]]; then
    mkdir -p "$TMP_DIR/uploads"
    cp -r "$APP_DIR/public/uploads/videos" "$TMP_DIR/uploads/" 2>/dev/null || true
    V_SIZE=$(du -sh "$TMP_DIR/uploads/videos" 2>/dev/null | cut -f1)
    log "   ✓ Videos: $V_SIZE"
else
    log "   ⚠️  Folder videos tidak ada — skip"
fi

# ─── 3. Backup Thumbnails ───
log "🖼️  [3/4] Backup thumbnails..."
if [[ -d "$APP_DIR/public/uploads/thumbnails" ]]; then
    mkdir -p "$TMP_DIR/uploads"
    cp -r "$APP_DIR/public/uploads/thumbnails" "$TMP_DIR/uploads/" 2>/dev/null || true
    T_SIZE=$(du -sh "$TMP_DIR/uploads/thumbnails" 2>/dev/null | cut -f1)
    log "   ✓ Thumbnails: $T_SIZE"
else
    log "   ⚠️  Folder thumbnails tidak ada — skip"
fi

# ─── 4. Backup .env & config ───
log "⚙️  [4/4] Backup config..."
cp "$APP_DIR/.env" "$TMP_DIR/env.backup" 2>/dev/null || true
cat > "$TMP_DIR/manifest.json" << MANIFEST
{
    "timestamp": "$TIMESTAMP",
    "app": "videovault",
    "version": "2.1.0",
    "db_name": "$DB_NAME",
    "includes": ["database.sql", "uploads/", "env.backup"],
    "created_at": "$(date -Iseconds)",
    "hostname": "$(hostname)"
}
MANIFEST
log "   ✓ Config + manifest"

# ─── Compress ───
log "🗜️  Compressing..."
tar -czf "$ARCHIVE" -C "$TMP_DIR" . 2>/dev/null || fail "Compress gagal"
rm -rf "$TMP_DIR"

ARCHIVE_SIZE=$(du -h "$ARCHIVE" | cut -f1)
log "   ✓ Archive: $ARCHIVE_SIZE"

# ─── Retention (hapus > 30 hari) ───
log "🧹 Retention: hapus backup > $RETENTION_DAYS hari..."
DELETED=$(find "$BACKUP_DIR" -name "*.tar.gz" -type f -mtime "+$RETENTION_DAYS" -print -delete | wc -l)
log "   ✓ Deleted: $DELETED file"

# ─── Summary ───
TOTAL_BACKUPS=$(find "$BACKUP_DIR" -name "*.tar.gz" -type f | wc -l)
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" 2>/dev/null | cut -f1)

log "════════════════════════════════════════════"
log "✅ Backup selesai: $ARCHIVE"
log "📊 Total backups: $TOTAL_BACKUPS ($TOTAL_SIZE)"
log "════════════════════════════════════════════"
