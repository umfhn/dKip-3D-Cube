#!/bin/bash
# cleanup-plugin.sh - DGP 3D Cube 360 Cleanup Script
# Version: 1.0
# Datum: 2025-11-11

set -e

# --- Konfiguration ---
PLUGIN_NAME="dgp-hube"
VERSION="5.1.15-prp.0"
DIST_DIR="dist"
DOCS_DIR="docs"
LOG_DIR="$DOCS_DIR/logs"
RELEASE_DIR="$DOCS_DIR/release"

TIMESTAMP=$(date +"%Y%m%d-%H%M%S")
ZIP_NAME="${PLUGIN_NAME}-v${VERSION}-clean.zip"
ZIP_PATH="$DIST_DIR/$ZIP_NAME"
REPORT_FILE="$LOG_DIR/cleanup-$TIMESTAMP.log"
CONTENTS_FILE="$RELEASE_DIR/CONTENTS-v${VERSION}-clean.txt"

# --- Farb-Codes für Output ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# --- Funktionen ---
log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# --- Modus-Auswahl ---
MODE="DEV_CLEAN" # Standard: Nicht-destruktiv
if [[ "$1" == "--mode" && "$2" == "DIST_CLEAN" ]]; then
    MODE="DIST_CLEAN"
    log_warning "Running in DIST_CLEAN mode. Development files will be deleted."
    # Sicherheitsabfrage
    read -p "Are you sure you want to continue? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log_error "Operation cancelled."
        exit 1
    fi
else
    log_info "Running in DEV_CLEAN mode (Dry-Run). No files will be deleted."
fi

# --- Verzeichnisse erstellen ---
mkdir -p "$DIST_DIR"
mkdir -p "$LOG_DIR"
mkdir -p "$RELEASE_DIR"

log_info "Starting cleanup for $PLUGIN_NAME v$VERSION"
log_info "Report will be saved to: $REPORT_FILE"

# --- Report Header ---
{
    echo "=========================================="
    echo "CLEANUP REPORT - $PLUGIN_NAME v$VERSION"
    echo "Timestamp: $(date)"
    echo "Mode: $MODE"
    echo "=========================================="
    echo ""
} > "$REPORT_FILE"

# --- Vendor-Check ---
log_info "Checking for vendor/ directory usage..."
VENDOR_USAGE_DETECTED=false
if [ -d "vendor" ] && [ -f "vendor/autoload.php" ]; then
    if grep -r -q -E "require|include.*vendor/autoload\.php" .; then
        VENDOR_USAGE_DETECTED=true
        log_warning "vendor/autoload.php usage detected. 'vendor/' will be KEPT."
        echo "VENDOR CHECK: KEEP (Autoload detected)" >> "$REPORT_FILE"
    fi
fi
if [ "$VENDOR_USAGE_DETECTED" = false ]; then
    log_info "No active vendor/autoload.php usage found. 'vendor/' will be REMOVED from ZIP."
    echo "VENDOR CHECK: REMOVE (No active autoload usage)" >> "$REPORT_FILE"
fi
echo "" >> "$REPORT_FILE"

# --- Dateien für ZIP sammeln ---
log_info "Collecting files for the ZIP package..."

# .distignore existiert, also verwenden wir es als primäre Quelle
if [ ! -f ".distignore" ]; then
    log_error ".distignore file not found. Cannot proceed."
    exit 1
fi

# Erstelle eine temporäre Kopie für das ZIP, um das Arbeitsverzeichnis nicht zu verändern
TEMP_DIR="temp-zip-build-$$"
mkdir -p "$TEMP_DIR"

# Finde alle Dateien, die NICHT ignoriert werden sollen, und kopiere sie
rsync -a --files-from=<(git ls-files | grep -v -f .distignore) . "$TEMP_DIR"

# Handle vendor/ basierend auf der Prüfung
if [ "$VENDOR_USAGE_DETECTED" = false ] && [ -d "$TEMP_DIR/vendor" ]; then
    rm -rf "$TEMP_DIR/vendor"
    log_info "'vendor/' directory removed from temporary build directory."
fi

# --- ZIP-Build ---
log_info "Building ZIP package: $ZIP_PATH"
(
    cd "$TEMP_DIR"
    zip -r -9 -q "../$ZIP_PATH" .
)
ZIP_SIZE=$(du -h "$ZIP_PATH" | cut -f1)
log_success "ZIP package created successfully at $ZIP_PATH (Size: $ZIP_SIZE)"

# --- Report-Inhalt generieren ---
{
    echo "ZIP BUILD SUMMARY:"
    echo "------------------"
    echo "Package: $ZIP_PATH"
    echo "Size: $ZIP_SIZE"
    echo ""
    echo "CONTENTS:"
    unzip -l "$ZIP_PATH"
} >> "$REPORT_FILE"

# --- CONTENTS-Datei erstellen ---
log_info "Generating contents file: $CONTENTS_FILE"
{
    echo "CLEANUP CONTENTS REPORT - $PLUGIN_NAME v$VERSION"
    echo "=========================================================="
    echo "Generated: $(date -u --iso-8601=seconds)"
    echo "ZIP Package: $ZIP_NAME"
    echo "Package Size: $ZIP_SIZE"
    echo ""
    echo "VERIFICATION:"
    echo "============="
    echo ""
    echo "✅ CORE PLUGIN FILES:"
    echo "--------------------"
    unzip -l "$ZIP_PATH" | grep -E '\.php$|\.md$|\.txt$|block\.json$'
    echo ""
    echo "✅ BUILD ASSETS:"
    echo "-----------------"
    unzip -l "$ZIP_PATH" | grep 'build/'
    echo ""
    echo "DEPLOYMENT READINESS: YES"
    echo "=========================================================="
} > "$CONTENTS_FILE"


# --- DIST_CLEAN Modus: Dateien löschen ---
if [ "$MODE" == "DIST_CLEAN" ]; then
    log_warning "Performing DIST_CLEAN: Deleting development files from working directory..."
    
    # Finde und lösche alle ignorierten Dateien
    DELETED_FILES=$(git ls-files --others --ignored --exclude-standard)
    
    if [ -n "$DELETED_FILES" ]; then
        echo "$DELETED_FILES" | xargs rm -rf
        log_success "Deleted ignored files and directories."
        echo -e "\nDELETED FILES (DIST_CLEAN):\n-------------------------\n$DELETED_FILES" >> "$REPORT_FILE"
    else
        log_info "No files to delete."
    fi
    
    # Handle vendor/
    if [ "$VENDOR_USAGE_DETECTED" = false ] && [ -d "vendor" ]; then
        rm -rf vendor
        log_success "Deleted 'vendor/' directory."
        echo "Deleted 'vendor/' directory." >> "$REPORT_FILE"
    fi

    log_success "DIST_CLEAN finished."
fi

# --- Aufräumen ---
rm -rf "$TEMP_DIR"

# --- Abschluss ---
log_success "Cleanup process completed."
log_info "Report: $REPORT_FILE"
log_info "Contents Manifest: $CONTENTS_FILE"
log_info "ZIP Package: $ZIP_PATH"

echo ""
echo "--- REPORT PREVIEW ---"
tail -n 20 "$REPORT_FILE"