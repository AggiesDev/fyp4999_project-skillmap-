# Skill Map — Logo Asset Package

This package contains ready-to-use exports of your Skill Map logo in the sizes and file
types most websites need, generated from the artwork you provided.

## Folder guide

### `favicon/`
Small square icon versions (the network + "AI" badge, no wordmark) for browser tabs.
- `favicon-16x16.png`, `favicon-32x32.png`, `favicon-48x48.png`, `favicon-64x64.png`,
  `favicon-96x96.png`, `favicon-128x128.png`
- `favicon.ico` — multi-resolution (16/32/48) classic favicon file

Add this to your HTML `<head>`:
```html
<link rel="icon" type="image/x-icon" href="/favicon/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16x16.png">
```

### `app-icons/`
Icons for mobile home screens, PWAs, and Android/iOS install prompts.
- `apple-touch-icon-180x180.png` (opaque white background, required by iOS)
- `android-chrome-192x192.png`, `android-chrome-512x512.png`
- `maskable-icon-512x512.png` (safe-zone padded for Android adaptive icon masks)
- `icon-16x16.png` … `icon-1024x1024.png` — general-purpose square icon set
- `site.webmanifest` — sample PWA manifest referencing the icons above

Add this to your HTML `<head>`:
```html
<link rel="apple-touch-icon" sizes="180x180" href="/app-icons/apple-touch-icon-180x180.png">
<link rel="manifest" href="/app-icons/site.webmanifest">
```

### `logo-full/`
The complete logo with the "SKILL MAP" wordmark, for headers, footers, login pages, etc.
Each size is available as:
- `logo-transparent-{width}w.png` — transparent background (best for dark or colored sections)
- `logo-white-bg-{width}w.png` / `.jpg` — flattened on white
- `logo-transparent-{width}w.webp` — smaller file size for faster page loads

Widths included: 2000w, 1600w, 1200w, 800w, 512w, 256w.

### `source/`
The highest-resolution master files everything else was generated from — keep these for
future re-exports:
- `icon-master-transparent.png` — square icon-only artwork, transparent
- `logo-full-master-transparent.png` — full logo with wordmark, transparent
- `logo-full-master-white-bg.png` — full logo with wordmark, white background

## Notes
- The original artwork you provided was 1024×715px, so anything above ~1200px wide is
  slightly upscaled. For very large uses (e.g. large-format print or a huge hero banner),
  ask for a re-export from your original design file for the sharpest result.
- All transparent PNGs had their white background removed automatically; if you spot any
  faint white fringing at the edges when placed on a dark background, a quick touch-up in
  Canva or Photoshop will clean it up.
