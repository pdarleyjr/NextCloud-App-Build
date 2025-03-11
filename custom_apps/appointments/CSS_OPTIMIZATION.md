# CSS Optimization Guide

This documentation outlines the improvements made to the CSS codebase of the Nextcloud Appointments app to resolve parsing errors and improve compatibility with modern browsers.

## Issues Fixed

### 1. Legacy Internet Explorer Filters

**Problem:** The codebase contained outdated Internet Explorer-specific filters using the `progid:DXImageTransform.Microsoft` syntax.

```css
/* Before */
filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#f00', endColorstr='#0f0', GradientType=1);
```

**Solution:** Removed these filters and replaced them with modern CSS alternatives where necessary.

```css
/* After */
background: linear-gradient(to right, red, green);
```

### 2. Pseudo-Element Notation

**Problem:** The codebase used single-colon pseudo-element notation (`:before`, `:after`), which is deprecated in modern CSS.

```css
/* Before */
.element:before {
  content: "";
}
```

**Solution:** Updated all pseudo-elements to use the double-colon notation.

```css
/* After */
.element::before {
  content: "";
}
```

### 3. Color Function Notation

**Problem:** The codebase used the older `rgba()` format with comma-separated values.

```css
/* Before */
color: rgba(0, 0, 0, 0.5);
```

**Solution:** Updated to modern color function notation.

```css
/* After */
color: rgb(0 0 0 / 50%);
```

### 4. Alpha Value Notation

**Problem:** The codebase used decimal alpha values (e.g., `opacity: 0.5`).

```css
/* Before */
opacity: 0.75;
```

**Solution:** Converted all alpha values to percentage notation.

```css
/* After */
opacity: 75%;
```

### 5. Vendor Prefixes

**Problem:** The codebase contained numerous vendor-prefixed properties that are no longer necessary.

```css
/* Before */
-webkit-animation: fadeIn 1s;
-moz-animation: fadeIn 1s;
animation: fadeIn 1s;
```

**Solution:** Implemented Autoprefixer to automatically manage vendor prefixes based on current browser requirements.

```css
/* After - Autoprefixer handles this automatically */
animation: fadeIn 1s;
```

### 6. Syntax Errors

**Problem:** There were several syntax errors, including empty declarations and misplaced semicolons.

```css
/* Before */
.some-class {
  ; 
  color: red;
}
```

**Solution:** Fixed all syntax errors to ensure valid CSS.

```css
/* After */
.some-class {
  color: red;
}
```

## Tools Implemented

### 1. Stylelint

Installed and configured Stylelint with SCSS-specific rules to catch CSS syntax errors during development.

Configuration file: `.stylelintrc.json`

### 2. PostCSS with Autoprefixer

Added PostCSS with Autoprefixer to automatically handle vendor prefixes based on the latest browser compatibility data.

### 3. Custom CSS Fixing Script

Created a specialized script (`scripts/fix-css-issues.js`) that automatically detects and fixes common CSS issues, including:

- Legacy IE filter removal
- Pseudo-element notation updates
- Color function modernization
- Alpha value percentage conversion
- Vendor prefix normalization
- Syntax error correction

## Usage

### Running the CSS Fixes

```bash
# Fix CSS issues and apply Autoprefixer
npm run fix:css

# Lint CSS/SCSS files
npm run lint:css
```

### Checking for CSS Issues

To check for remaining CSS issues:

```bash
npm run lint:css
```

## Best Practices for Future Development

1. **Use double-colon notation for pseudo-elements**: 
   - Use `::before`, `::after`, `::placeholder`, etc.

2. **Use modern color syntax**:
   - Instead of `rgba(0, 0, 0, 0.5)`, use `rgb(0 0 0 / 50%)`

3. **Use percentage alpha values**:
   - Instead of `opacity: 0.5`, use `opacity: 50%`

4. **Avoid vendor prefixes**:
   - Let Autoprefixer handle vendor prefixes automatically

5. **Run the linter before commits**:
   - Use `npm run lint:css` to catch issues early

## Troubleshooting

If you encounter CSS parsing errors in the browser console:

1. Check for syntax errors with `npm run lint:css`
2. Run the fix script with `npm run fix:css`
3. For specific browser compatibility issues, check [caniuse.com](https://caniuse.com/)