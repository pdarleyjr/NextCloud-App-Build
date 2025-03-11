#!/usr/bin/env node

/**
 * This script scans and fixes common CSS parsing issues:
 * - Removes Internet Explorer-specific filters (progid:DXImageTransform)
 * - Replaces legacy syntax with modern alternatives
 * - Fixes common syntax errors
 * - Updates single colon pseudo-elements to double colon
 * - Modernizes color function notation
 */

const fs = require('fs');
const path = require('path');
const glob = require('glob');

// Define patterns to search for
const patterns = [
  {
    // IE Filters
    regex: /filter\s*:\s*progid:DXImageTransform\.Microsoft\.[^;]+;?/g,
    replacement: '/* Removed IE filter */'
  },
  {
    // Alpha filters
    regex: /filter\s*:\s*alpha\([^)]+\);?/g,
    replacement: '/* Removed alpha filter */'
  },
  {
    // -moz prefixes (can be handled by Autoprefixer)
    regex: /-moz-([a-zA-Z-]+)\s*:/g,
    replacement: '/* -moz removed */ $1:'
  },
  {
    // -ms-clear and -ms-input-placeholder
    regex: /(-ms-clear|:-ms-input-placeholder|:-moz-placeholder)\s*{[^}]*}/g,
    replacement: '/* Removed unsupported MS pseudo-element */'
  },
  {
    // line-clamp used incorrectly (without webkit prefix)
    regex: /line-clamp\s*:/g,
    replacement: '-webkit-line-clamp:'
  },
  {
    // Fix empty declarations with semicolons
    regex: /{\s*;\s*/g,
    replacement: '{ '
  },
  {
    // Convert single colon pseudo-elements to double colon
    regex: /(^|\s|,):before(\s|{|:)/g,
    replacement: '$1::before$2'
  },
  {
    // Convert single colon pseudo-elements to double colon
    regex: /(^|\s|,):after(\s|{|:)/g,
    replacement: '$1::after$2'
  },
  {
    // Convert single colon pseudo-elements to double colon
    regex: /(^|\s|,):placeholder(\s|{|:)/g,
    replacement: '$1::placeholder$2'
  },
  {
    // Convert rgba to modern color function - rgba(0,0,0,0.5) to rgb(0 0 0 / 50%)
    regex: /rgba\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*([\d.]+)\s*\)/g,
    replacement: (match, r, g, b, a) => {
      // Convert decimal alpha values to percentage
      const alphaPercentage = Math.round(parseFloat(a) * 100) + '%';
      return `rgb(${r} ${g} ${b} / ${alphaPercentage})`;
    }
  },
  {
    // Remove -webkit-keyframes and replace with just keyframes
    regex: /@-webkit-keyframes\s+([a-zA-Z0-9_-]+)/g,
    replacement: '@keyframes $1'
  },
  {
    // Fix comment spacing in CSS: /*comment*/ becomes /* comment */
    regex: /\/\*([^\s])(.*?)([^\s])\*\//g,
    replacement: '/* $1$2$3 */'
  },
  {
    // Convert decimal alpha values to percentage in various CSS properties
    regex: /(opacity|fill-opacity|stroke-opacity|stop-opacity):\s*(0?\.[0-9]+)\s*;/g,
    replacement: (match, property, alphaValue) => {
      // Convert decimal alpha values to percentage
      const alphaPercentage = Math.round(parseFloat(alphaValue) * 100) + '%';
      return `${property}: ${alphaPercentage};`;
    }
  },
  {
    // Convert opacity: 0; to opacity: 0%;
    regex: /(opacity|fill-opacity|stroke-opacity|stop-opacity):\s*0\s*;/g,
    replacement: '$1: 0%;'
  },
  {
    // Convert opacity: 1; to opacity: 100%;
    regex: /(opacity|fill-opacity|stroke-opacity|stop-opacity):\s*1\s*;/g,
    replacement: '$1: 100%;'
  },
  {
    // Convert all single-colon pseudo-elements to double-colon
    regex: /([^:]):(before|after|first-line|first-letter|placeholder|selection)(\s|{|,|:)/g,
    replacement: '$1::$2$3'
  },
  {
    // Convert -webkit-keyframes to standard keyframes
    regex: /@-webkit-keyframes\s+([^{]+){/g,
    replacement: '@keyframes $1{'
  },
  {
    // Fix empty declarations or extra semicolons
    regex: /;\s*;/g,
    replacement: ';'
  }
];

// Find all CSS and SCSS files in the project
const cssFiles = glob.sync('./css/**/*.css');
const scssFiles = glob.sync('./scss/**/*.scss');
const allFiles = [...cssFiles, ...scssFiles];

console.log(`Found ${allFiles.length} CSS/SCSS files to process`);

// Process each file
let filesWithChanges = 0;
let totalChanges = 0;

allFiles.forEach(filePath => {
  let content = fs.readFileSync(filePath, 'utf8');
  let fileChanged = false;
  let fileChanges = 0;
  
  patterns.forEach(pattern => {
    const matches = content.match(pattern.regex);
    if (matches) {
      fileChanged = true;
      fileChanges += matches.length;
      totalChanges += matches.length;
      content = content.replace(pattern.regex, pattern.replacement);
      console.log(`  ${filePath}: Replaced ${matches.length} occurrences of ${pattern.regex}`);
    }
  });
  
  if (fileChanged) {
    fs.writeFileSync(filePath, content);
    filesWithChanges++;
    console.log(`Updated ${filePath} with ${fileChanges} changes`);
  }
});

console.log(`\nCompleted: ${filesWithChanges} files modified with ${totalChanges} total changes`);

// Now run autoprefixer on the CSS files to ensure modern syntax
console.log('\nTo apply modern syntax with Autoprefixer, run:');
console.log('npm run postcss');