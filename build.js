/**
 * Pre-compile the React+Babel prototype into a production single-file HTML.
 *
 * Input:  ui/project/바른케어플러스.html  (uses @babel/standalone in the browser)
 * Output: ui/project/index.html           (JSX pre-compiled, no in-browser Babel)
 *
 * Run:    node build.js
 */
const fs = require('fs');
const path = require('path');
const babel = require('@babel/core');

const SRC = path.join(__dirname, 'ui', 'project', '바른케어플러스.html');
const OUT = path.join(__dirname, 'ui', 'project', 'index.html');

const html = fs.readFileSync(SRC, 'utf8');

// Extract the <script type="text/babel">...</script> block.
const scriptRe = /<script type="text\/babel">([\s\S]*?)<\/script>/;
const match = html.match(scriptRe);
if (!match) {
  console.error('Could not find <script type="text/babel"> block in source.');
  process.exit(1);
}
const jsx = match[1];

// Compile JSX → ES2017 JS (browsers all support classes/arrows/spread).
const { code } = babel.transformSync(jsx, {
  babelrc: false,
  configFile: false,
  presets: [
    ['@babel/preset-react', { runtime: 'classic' }],
  ],
  // No `env` preset — modern browsers handle the rest natively, and React is
  // already loaded as a global UMD bundle from CDN.
  compact: false,
  comments: false,
  sourceMaps: false,
});

// Replace:
//   1. The babel-standalone CDN tag (no longer needed — JSX is pre-compiled).
//   2. The tweaks-panel.jsx tag (design-tool helper, never used in prod —
//      the app's `typeof useTweaks === 'function' ? … : […defaults…]` fallback
//      already handles its absence gracefully).
//   3. React + React-DOM dev builds → production minified builds. The SRI
//      `integrity=` hashes are tied to the dev URLs, so they're stripped too.
//   4. The <script type="text/babel">…</script> block → a normal <script>
//      with the pre-compiled output.
let out = html
  .replace(
    /\s*<script src="https:\/\/unpkg\.com\/@babel\/standalone[^"]+"[^>]*><\/script>/,
    ''
  )
  .replace(
    /\s*<script type="text\/babel" src="tweaks-panel\.jsx"><\/script>/,
    ''
  )
  .replace(
    /<script src="https:\/\/unpkg\.com\/react@([^/]+)\/umd\/react\.development\.js"[^>]*><\/script>/,
    '<script src="https://unpkg.com/react@$1/umd/react.production.min.js" crossorigin="anonymous"></script>'
  )
  .replace(
    /<script src="https:\/\/unpkg\.com\/react-dom@([^/]+)\/umd\/react-dom\.development\.js"[^>]*><\/script>/,
    '<script src="https://unpkg.com/react-dom@$1/umd/react-dom.production.min.js" crossorigin="anonymous"></script>'
  )
  .replace(scriptRe, `<script>\n${code}\n</script>`);

fs.writeFileSync(OUT, out, 'utf8');

const srcKb = (Buffer.byteLength(html, 'utf8') / 1024).toFixed(1);
const outKb = (Buffer.byteLength(out, 'utf8') / 1024).toFixed(1);
console.log(`Wrote ${path.relative(__dirname, OUT)}  (${outKb} KB, source was ${srcKb} KB)`);
console.log('Babel-standalone CDN dropped — ~700KB saved on first load.');
