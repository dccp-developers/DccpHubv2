# Manual GitHub Release Update Instructions

## Option 1: Using the Web Interface (Recommended)

1. Go to your [GitHub Releases page](https://github.com/yukazakiri/DccpHubv2/releases)
2. For each release (v1.0.1, v1.0.2, v1.1.0), click the "Edit" button
3. Replace the existing description with the content from the corresponding file:
   - `release_descriptions/v1.0.1.md` for v1.0.1
   - `release_descriptions/v1.0.2.md` for v1.0.2  
   - `release_descriptions/v1.1.0.md` for v1.1.0
4. Click "Update release"

## Option 2: Using the Script (Requires GitHub Token)

1. Create a GitHub Personal Access Token:
   - Go to GitHub Settings > Developer settings > Personal access tokens > Tokens (classic)
   - Click "Generate new token (classic)"
   - Select scopes: `repo` (Full control of private repositories)
   - Copy the token

2. Run the update script:
   ```bash
   ./update_releases.sh YOUR_GITHUB_TOKEN
   ```

## Option 3: Using curl commands directly

Replace `YOUR_GITHUB_TOKEN` with your actual GitHub token:

### Update v1.0.1:
```bash
curl -X PATCH \
  -H "Accept: application/vnd.github.v3+json" \
  -H "Authorization: token YOUR_GITHUB_TOKEN" \
  -H "Content-Type: application/json" \
  "https://api.github.com/repos/yukazakiri/DccpHubv2/releases/237218496" \
  -d @- << 'EOF'
{
  "body": "$(cat release_descriptions/v1.0.1.md | jq -Rs .)"
}
EOF
```

### Update v1.0.2:
```bash
curl -X PATCH \
  -H "Accept: application/vnd.github.v3+json" \
  -H "Authorization: token YOUR_GITHUB_TOKEN" \
  -H "Content-Type: application/json" \
  "https://api.github.com/repos/yukazakiri/DccpHubv2/releases/237218495" \
  -d @- << 'EOF'
{
  "body": "$(cat release_descriptions/v1.0.2.md | jq -Rs .)"
}
EOF
```

### Update v1.1.0:
```bash
curl -X PATCH \
  -H "Accept: application/vnd.github.v3+json" \
  -H "Authorization: token YOUR_GITHUB_TOKEN" \
  -H "Content-Type: application/json" \
  "https://api.github.com/repos/yukazakiri/DccpHubv2/releases/237218494" \
  -d @- << 'EOF'
{
  "body": "$(cat release_descriptions/v1.1.0.md | jq -Rs .)"
}
EOF
```

## What's Been Improved

The new descriptions include:

### v1.0.1
- Detailed build system improvements
- Technical specifications
- Clear feature descriptions

### v1.0.2  
- Icon and visual improvements
- Bug fixes for Android app drawer
- PWA integration details

### v1.1.0
- Complete attendance management system
- Faculty tools and missing student requests
- Mobile UI/UX overhaul
- Technical architecture improvements

All descriptions are now comprehensive, professional, and accurately reflect the actual changes made in each release based on the commit analysis.
