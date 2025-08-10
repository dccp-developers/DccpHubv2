#!/bin/bash

# Script to update GitHub release descriptions
# Usage: ./update_releases.sh [GITHUB_TOKEN]

set -e

REPO="yukazakiri/DccpHubv2"
GITHUB_TOKEN="${1:-$GITHUB_TOKEN}"

if [ -z "$GITHUB_TOKEN" ]; then
    echo "Error: GitHub token required"
    echo "Usage: $0 [GITHUB_TOKEN]"
    echo "Or set GITHUB_TOKEN environment variable"
    exit 1
fi

echo "Updating GitHub releases for $REPO..."

# Function to update a release
update_release() {
    local release_id="$1"
    local description_file="$2"
    local version="$3"
    
    echo "Updating release $version (ID: $release_id)..."
    
    if [ ! -f "$description_file" ]; then
        echo "Error: Description file $description_file not found"
        return 1
    fi
    
    # Read the description and escape it for JSON
    local description=$(cat "$description_file" | jq -Rs .)
    
    # Update the release
    curl -X PATCH \
        -H "Accept: application/vnd.github.v3+json" \
        -H "Authorization: token $GITHUB_TOKEN" \
        -H "Content-Type: application/json" \
        "https://api.github.com/repos/$REPO/releases/$release_id" \
        -d "{\"body\": $description}" \
        --silent --show-error
    
    if [ $? -eq 0 ]; then
        echo "✅ Successfully updated $version"
    else
        echo "❌ Failed to update $version"
        return 1
    fi
}

# Update each release
echo ""
echo "Updating v1.0.1..."
update_release "237218496" "release_descriptions/v1.0.1.md" "v1.0.1"

echo ""
echo "Updating v1.0.2..."
update_release "237218495" "release_descriptions/v1.0.2.md" "v1.0.2"

echo ""
echo "Updating v1.1.0..."
update_release "237218494" "release_descriptions/v1.1.0.md" "v1.1.0"

echo ""
echo "🎉 All releases updated successfully!"
echo "Visit https://github.com/$REPO/releases to see the updated descriptions."
