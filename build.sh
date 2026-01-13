#!/bin/bash

# ChatStory Plugin Build Script
# Creates a production-ready zip file with Composer dependencies

set -e

PLUGIN_SLUG="chatstory"
BUILD_DIR="build"
PLUGIN_DIR="$BUILD_DIR/$PLUGIN_SLUG"

echo "🔨 Building ChatStory plugin..."

# Clean up old build
if [ -d "$BUILD_DIR" ]; then
    echo "   Cleaning old build directory..."
    rm -rf "$BUILD_DIR"
fi

# Create fresh build directory
echo "   Creating build directory..."
mkdir -p "$PLUGIN_DIR"

# Copy plugin files
echo "   Copying plugin files..."
rsync -av --exclude-from='.buildignore' . "$PLUGIN_DIR/"

# Install production Composer dependencies
echo "   Installing Composer dependencies (production only)..."
cd "$PLUGIN_DIR"
composer install --no-dev --optimize-autoloader --no-interaction
cd ../..

# Create zip
echo "   Creating zip file..."
cd "$BUILD_DIR"
zip -r "../${PLUGIN_SLUG}.zip" "$PLUGIN_SLUG" -q
cd ..

# Cleanup
echo "   Cleaning up..."
rm -rf "$BUILD_DIR"

echo "✅ Build complete! Created: ${PLUGIN_SLUG}.zip"
echo "   You can now upload this zip to WordPress."
