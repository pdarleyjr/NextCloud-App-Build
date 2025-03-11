# NextCloud Appointments App Modernization

This PR addresses several issues to modernize the Appointments app, optimize build performance, and fix deprecation warnings.

## Improvements Made

### 1. Sass Modernization
- Migrated from deprecated `@import` to `@use` module system in SCSS files
- Added namespacing for imported Sass modules
- Added `sass:color` module for modern color functions
- Updated Sass to version 1.63.6 and sass-loader to 13.3.2
- Created `verify-scss-files.sh` script to check for deprecated Sass syntax

### 2. Build Performance Optimization
- Added `NODE_OPTIONS=--max-old-space-size=4096` to build scripts for improved memory allocation
- Set `sideEffects: false` in package.json for better tree shaking
- Webpack production configuration now includes filesystem caching for faster builds
- Added build:analyze script to help identify large dependencies

### 3. DevOps Improvements
- Created a customized .devcontainer configuration for improved GitHub Codespaces development
- Added pre-installed dependencies in the Docker configuration
- Updated Node.js requirement to >=16.0.0 (from >=10.0.0)
- Optimized VSCode settings for better performance

### 4. CSS and Vendor Prefix Management
- Ensured PostCSS and Autoprefixer are correctly configured for handling vendor prefixes
- Removed legacy IE filter syntax where appropriate
- Confirmed postcss-loader is properly integrated in webpack config

## Testing Notes
- The build process should now be more efficient and use less memory
- SCSS files should compile without deprecation warnings
- GitHub Codespaces development should be smoother with the customized container

## Notes for Reviewers
- The Sass migration may require testing across different browsers to ensure styles render correctly
- Build performance improvements should be noticeable on large builds