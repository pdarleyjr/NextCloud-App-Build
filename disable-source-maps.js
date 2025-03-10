/**
 * Script to disable source map loading in the browser
 * This can be executed in the browser console as a quick fix for development
 * 
 * Usage:
 * 1. Open browser developer tools (F12 or Ctrl+Shift+I)
 * 2. Go to the Console tab
 * 3. Copy and paste this entire script
 * 4. Press Enter to execute
 */

(function() {
  // For Chrome/Edge
  if (typeof chrome !== 'undefined' && chrome.devtools) {
    try {
      // Attempt to disable source maps in Chrome/Edge
      const settings = JSON.parse(localStorage.getItem('devtools-preferences') || '{}');
      settings['cssSourceMapsEnabled'] = false;
      settings['jsSourceMapsEnabled'] = false;
      localStorage.setItem('devtools-preferences', JSON.stringify(settings));
      console.log('✅ Source maps disabled for Chrome/Edge. Please reload DevTools.');
    } catch (e) {
      console.error('❌ Failed to disable source maps:', e);
    }
  } 
  // For Firefox
  else if (typeof InstallTrigger !== 'undefined') {
    console.log('ℹ️ Firefox: Please manually disable source maps:');
    console.log('1. Open DevTools Settings (F1 or ⚙️ icon)');
    console.log('2. Uncheck "Enable Source Maps"');
  }
  // For Safari
  else if (/^((?!chrome|android).)*safari/i.test(navigator.userAgent)) {
    console.log('ℹ️ Safari: Please manually disable source maps:');
    console.log('1. Open Develop menu > Web Inspector > Settings');
    console.log('2. Uncheck "Enable Source Maps"');
  }
  // For other browsers
  else {
    console.log('ℹ️ Please manually disable source maps in your browser\'s developer tools settings.');
  }
  
  // Add a MutationObserver to prevent source map requests
  const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      if (mutation.type === 'childList') {
        for (const node of mutation.addedNodes) {
          if (node.tagName === 'LINK' && node.rel === 'stylesheet' && node.href.includes('sourceMappingURL')) {
            node.remove();
            console.log('🛑 Prevented source map request:', node.href);
          }
        }
      }
    }
  });
  
  observer.observe(document.head, { childList: true, subtree: true });
  console.log('👀 Now monitoring and blocking source map requests');
  
  return '✅ Source map blocking script initialized';
})();