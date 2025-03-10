# Nextcloud Development Troubleshooting Guide

This guide addresses two primary issues encountered during Nextcloud development in GitHub Codespaces:

1. **Missing Source Files (404 Errors)** → Source maps not resolving correctly
2. **Bounce Tracker Notification** → GitHub Codespaces session management issue

## 1. Fixing 404 Errors for SCSS/CSS Files

### Understanding the Issue

The browser is trying to load original SCSS/CSS files that are either:
- Not generated properly during the build process
- Not mapped correctly in the source maps
- Not included in the final deployment
- Being served from an incorrect URL

### Solutions

#### 1. Verify and Adjust Source Map Configuration

We've updated the webpack configuration files to properly handle source maps:
- `webpack.prod.js`: Enabled source maps in production mode
- `webpack.common.js`: Added sourceMap: true to the SCSS loaders
- `webpack.dev.js`: Changed from 'cheap-source-map' to 'source-map' for consistency

#### 2. Verify and Rebuild SCSS/CSS Files

Use the provided script to check for missing SCSS/CSS files and rebuild them if necessary:

```bash
./verify-scss-files.sh
```

This script:
- Checks if the CSS directory exists and creates it if needed
- Verifies if the main CSS files exist and rebuilds them if missing
- Runs Nextcloud maintenance commands to repair and update the theme

#### 3. Disable Source Maps in Browser (Quick Fix)

For a quick fix during development, you can disable source map loading in the browser:

1. Open your browser's developer tools (F12)
2. Go to the Console tab
3. Copy and paste the contents of `disable-source-maps.js`
4. Press Enter to execute

Alternatively, you can manually disable source maps in your browser's developer tools settings.

## 2. Fixing GitHub Bounce Tracker Notification

### Understanding the Issue

GitHub Codespaces automatically expires inactive sessions after 3,600 seconds (1 hour) by default. Security mechanisms may classify GitHub as a "bounce tracker" if interactions are not detected.

### Solutions

#### 1. Keep Session Alive

We've created a script to send periodic requests to GitHub API to keep your session active:

```bash
./keep-session-alive.sh
```

This script pings GitHub every 5 minutes to prevent session timeout.

#### 2. Modified Codespace Environment Settings

We've updated the `.devcontainer/devcontainer.json` file to:
- Set `idleTimeout` to 2 hours
- Add a `postAttachCommand` that automatically runs the keep-session-alive script

These changes will take effect the next time you rebuild or reopen your codespace.

#### 3. Browser Session Keepalive

If you're still experiencing issues, you can add a keepalive script directly in your browser:

1. Open DevTools (F12)
2. Enter in Console:
   ```js
   setInterval(() => fetch("https://github.com"), 300000);
   ```

This prevents GitHub from classifying the session as idle.

## Additional Resources

- **Webpack Documentation**: [Source Maps](https://webpack.js.org/configuration/devtool/)
- **GitHub Codespaces Documentation**: [Timeouts](https://docs.github.com/en/codespaces/developing-in-codespaces/codespaces-lifecycle#timeout-periods)
- **Nextcloud Development Documentation**: [Developer Manual](https://docs.nextcloud.com/server/latest/developer_manual/)

## Troubleshooting Scripts

| Script | Purpose |
|--------|---------|
| `keep-session-alive.sh` | Prevents GitHub Codespaces session timeout |
| `verify-scss-files.sh` | Checks and rebuilds SCSS/CSS files |
| `disable-source-maps.js` | Browser script to disable source map loading |

For any further issues, please refer to the official Nextcloud documentation or open an issue in the repository.