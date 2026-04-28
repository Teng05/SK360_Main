# Fix Dyte CDN Import Error — Option A (Quick Fix)

## Problem
Dynamic `import()` from jsdelivr CDN fails with:
`Failed to fetch dynamically imported module: https://cdn.jsdelivr.net/npm/@dytesdk/web-core@1.37.0/dist/index.esm.js`

## Steps
- [x] Update CDN URLs in `meeting-call.blade.php` to use reliable ES module endpoints
- [x] Clear Laravel view cache
- [x] Verify fix

