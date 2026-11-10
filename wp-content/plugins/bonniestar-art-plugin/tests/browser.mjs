// Dependency-free interaction checks in a real installed Chrome/Chromium.
import assert from 'node:assert/strict';
import { createServer } from 'node:http';
import { mkdtemp, readFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { basename, resolve, sep } from 'node:path';
import { spawn } from 'node:child_process';

const executable = process.argv[2];
if (!executable || !globalThis.WebSocket) throw new Error('Use node --experimental-websocket tests/browser.mjs <Chrome executable>.');
const asset = name => readFile(new URL(`../assets/${name}`, import.meta.url), 'utf8');
const variables = '--fixture-surface:papayawhip;--fixture-text:midnightblue;--fixture-accent:maroon';
const labels = {title:'Images', open:'Enlarge', close:'Close', previous:'Previous', next:'Next', loading:'Loading', error:'Image failed', original:'Open original', position:'%1$s of %2$s'};
const figure = (name, source, extra = '') => `<figure class="wp-block-image" ${extra}><img src="/thumb.svg" data-gallery-lightbox-src="${source}" alt="${name}"><figcaption>${name} caption</figcaption></figure>`;
const html = `<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1">
<style>:root{${variables}}body{margin:0;font:18px/1.7 system-ui}.wp-block-gallery{display:flex;gap:1rem}.wp-block-image{margin:1rem;max-width:180px}img{max-width:100%}</style>
<link rel="stylesheet" href="/lightbox.css"><style>.wp-gallery-lightbox{--gallery-lightbox-surface:var(--fixture-surface);--gallery-lightbox-text:var(--fixture-text);--gallery-lightbox-accent:var(--fixture-accent)}</style>
<script>window.wpGalleryLightbox=${JSON.stringify(labels)}</script><script defer src="/lightbox.js"></script></head><body>
<div id="first" class="wp-block-gallery">${figure('A','/large-a.svg')}${figure('B','/large-b.svg')}${figure('Broken','/missing.jpg')}</div>
<div id="race" class="wp-block-gallery">${figure('Slow','/slow.svg')}${figure('Fast','/large-fast.svg')}</div>
<div id="single">${figure('Single','/large-single.svg')}</div>
<figure class="wp-block-image no-lightbox" id="optout"><img src="/thumb.svg" alt="Excluded"></figure>
<figure class="wp-block-image" id="custom"><a href="/contact"><img src="/thumb.svg" alt="Custom link"></a></figure>
<figure class="wp-block-image" id="download"><a href="/large-a.svg" download><img src="/thumb.svg" alt="Download"></a></figure>
<figure class="wp-block-image" id="external"><img src="/thumb.svg" srcset="/external-small.svg 180w, /external-large.svg 1600w" alt="External"></figure>
<figure class="wp-block-image" id="density"><img src="/thumb.svg" srcset="/density-small.svg 1x, /density-large.svg 2x" alt="Density"></figure>
<figure class="wp-block-image" id="unsafe"><a href="javascript:void(0)"><img src="/thumb.svg" alt="Unsafe"></a></figure>
<div id="dynamic"></div></body></html>`;
const requests = [];
const server = createServer(async (req, res) => {
	requests.push(req.url);
	if (req.url === '/lightbox.js' || req.url === '/lightbox.css') {
		res.setHeader('Content-Type', req.url.endsWith('.js') ? 'text/javascript' : 'text/css');
		res.end(await asset(req.url.slice(1)));
	} else if (req.url.endsWith('.svg')) {
		if (req.url === '/slow.svg') await new Promise(done => setTimeout(done, 800));
		const small = req.url.includes('thumb') || req.url.includes('small');
		res.setHeader('Content-Type', 'image/svg+xml');
		res.end(`<svg xmlns="http://www.w3.org/2000/svg" width="${small ? 180 : 1600}" height="${small ? 120 : 1000}"><rect width="100%" height="100%" fill="tan"/></svg>`);
	} else if (req.url === '/') { res.setHeader('Content-Type', 'text/html'); res.end(html); }
	else { res.writeHead(404); res.end('Not found'); }
});
await new Promise(done => server.listen(0, '127.0.0.1', done));
const origin = `http://127.0.0.1:${server.address().port}`;
const directory = await mkdtemp(resolve(tmpdir(), 'wp-lightbox-browser-'));
const browser = spawn(executable, ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--remote-debugging-port=0', `--user-data-dir=${directory}`, 'about:blank'], {stdio:'ignore', windowsHide:true, shell:false});
let ws;
let id = 0;
const pending = new Map();
function call(method, params = {}) {
	return new Promise((done, fail) => {
		const key = ++id;
		const timer = setTimeout(() => { pending.delete(key); fail(new Error(`${method} timed out`)); }, 15000);
		pending.set(key, {done, fail, timer});
		ws.send(JSON.stringify({id:key, method, params}));
	});
}
async function evaluate(expression) {
	const result = await call('Runtime.evaluate', {expression, returnByValue:true, awaitPromise:true});
	if (result.exceptionDetails) throw new Error(JSON.stringify(result.exceptionDetails));
	return result.result.value;
}
async function until(expression) {
	for (let tries=0; tries<100; tries++) {
		if (await evaluate(expression)) return;
		await new Promise(done => setTimeout(done, 50));
	}
	throw new Error(`Condition did not become true: ${expression}`);
}
const click = selector => evaluate(`document.querySelector(${JSON.stringify(selector)}).click()`);
const loaded = () => until('document.querySelector("dialog[open] .wp-gallery-lightbox__stage")?.getAttribute("aria-busy") === "false"');
async function key(name) {
	await call('Input.dispatchKeyEvent', {type:'keyDown', key:name, code:name, windowsVirtualKeyCode:name === 'Escape' ? 27 : name === 'Tab' ? 9 : 0});
	await call('Input.dispatchKeyEvent', {type:'keyUp', key:name, code:name});
}
try {
	let port;
	for (let tries=0; tries<100; tries++) {
		try { port = (await readFile(resolve(directory, 'DevToolsActivePort'), 'utf8')).split('\n')[0]; break; }
		catch { await new Promise(done => setTimeout(done, 100)); }
	}
	assert.ok(port, 'Chrome must expose its temporary debugging port');
	const tabs = await (await fetch(`http://127.0.0.1:${port}/json/list`)).json();
	ws = new WebSocket(tabs.find(tab => tab.type === 'page').webSocketDebuggerUrl);
	await new Promise((done, fail) => { ws.onopen = done; ws.onerror = fail; });
	ws.onmessage = event => {
		const message = JSON.parse(event.data), handler = pending.get(message.id);
		if (!handler) return;
		pending.delete(message.id); clearTimeout(handler.timer);
		message.error ? handler.fail(new Error(JSON.stringify(message.error))) : handler.done(message.result);
	};
	await call('Page.enable');
	await call('Emulation.setDeviceMetricsOverride', {width:1024, height:768, deviceScaleFactor:1, mobile:false});
	await call('Page.navigate', {url:origin});
	await until('document.readyState === "complete" && !!document.querySelector("#first button")');
	assert.ok(!requests.includes('/large-a.svg'), 'Do not fetch full gallery images before opening');
	assert.equal(await evaluate('!!document.querySelector("#optout button, #custom button, #download button, #unsafe button, #custom a[aria-haspopup]")'), false);
	await evaluate('document.documentElement.style.overflow = "auto"');
	await click('#first button'); await loaded();
	assert.equal(await evaluate('document.querySelector(".wp-gallery-lightbox__counter").textContent'), '1 of 3');
	assert.equal(await evaluate('document.querySelector(".wp-gallery-lightbox__image").naturalWidth'), 1600);
	assert.equal(await evaluate('document.querySelector(".wp-gallery-lightbox__caption").textContent'), 'A caption');
	assert.equal(await evaluate('getComputedStyle(document.querySelector("dialog")).backgroundColor'), 'rgb(255, 239, 213)', 'Theme CSS tokens must control the dialog');
	await key('ArrowRight'); await loaded();
	assert.ok(await evaluate('document.querySelector(".wp-gallery-lightbox__image").src.endsWith("/large-b.svg")'));
	await key('ArrowRight'); await loaded();
	assert.equal(await evaluate('document.querySelector(".wp-gallery-lightbox__status").textContent'), labels.error);
	assert.equal(await evaluate('document.querySelector(".wp-gallery-lightbox__original").hidden'), false);
	await key('Home'); await loaded();
	await key('ArrowLeft'); await loaded();
	assert.equal(await evaluate('document.querySelector(".wp-gallery-lightbox__counter").textContent'), '3 of 3');
	await key('Escape');
	assert.equal(await evaluate('document.querySelector("dialog").open'), false);
	await until('document.activeElement === document.querySelector("#first button")');
	assert.equal(await evaluate('document.activeElement === document.querySelector("#first button")'), true);
	assert.equal(await evaluate('document.documentElement.style.overflow'), 'auto');
	await click('#single button'); await loaded();
	assert.equal(await evaluate('[...document.querySelectorAll(".wp-gallery-lightbox__controls button")].slice(0,2).every(button => button.hidden)'), true);
	await key('Tab');
	assert.equal(await evaluate('document.querySelector("dialog").contains(document.activeElement)'), true);
	await key('Escape');
	await click('#race button');
	assert.equal(await evaluate('getComputedStyle(document.querySelector(".wp-gallery-lightbox__status")).position'), 'static');
	await click('.wp-gallery-lightbox__controls button:nth-child(2)'); await loaded();
	await new Promise(done => setTimeout(done, 950));
	assert.ok(await evaluate('document.querySelector(".wp-gallery-lightbox__image").src.endsWith("/large-fast.svg")'), 'Late loads must not replace the current image');
	await key('Escape');
	await click('#external button'); await loaded();
	assert.ok(await evaluate('document.querySelector(".wp-gallery-lightbox__image").src.endsWith("/external-large.svg")'));
	await key('Escape');
	await click('#density button'); await loaded();
	assert.ok(await evaluate('document.querySelector(".wp-gallery-lightbox__image").src.endsWith("/density-large.svg")'));
	await key('Escape');
	await evaluate(`document.querySelector('#dynamic').innerHTML = ${JSON.stringify(figure('Dynamic','/large-dynamic.svg'))}`);
	await until('!!document.querySelector("#dynamic button")');
	await click('#dynamic button'); await loaded();
	assert.ok(await evaluate('document.querySelector(".wp-gallery-lightbox__image").src.endsWith("/large-dynamic.svg")'));
	await key('Escape');
	await click('#first button'); await loaded();
	await call('Emulation.setTouchEmulationEnabled', {enabled:true});
	const point = await evaluate('(() => { const r=document.querySelector(".wp-gallery-lightbox__stage").getBoundingClientRect(); return {x:r.x+r.width/2, y:r.y+r.height/2}; })()');
	await call('Input.dispatchTouchEvent', {type:'touchStart', touchPoints:[point]});
	await call('Input.dispatchTouchEvent', {type:'touchMove', touchPoints:[{x:point.x-100,y:point.y}]});
	await call('Input.dispatchTouchEvent', {type:'touchEnd', touchPoints:[]});
	await loaded();
	assert.equal(await evaluate('document.querySelector(".wp-gallery-lightbox__counter").textContent'), '2 of 3', 'Horizontal touch gesture must navigate');
	for (const [width,height] of [[320,568],[390,844],[844,390],[1440,900]]) {
		await call('Emulation.setDeviceMetricsOverride', {width,height,deviceScaleFactor:1,mobile:false});
		await evaluate('new Promise(done => requestAnimationFrame(() => requestAnimationFrame(done)))');
		const fits = await evaluate('(() => { const d=document.querySelector("dialog"), i=d.querySelector("img"), s=d.querySelector(".wp-gallery-lightbox__stage"), r=i.getBoundingClientRect(), b=s.getBoundingClientRect(); return d.scrollWidth<=d.clientWidth && d.scrollHeight<=d.clientHeight+1 && r.width<=i.naturalWidth && r.height<=i.naturalHeight && r.width<=b.width+1 && r.height<=b.height+1; })()');
		assert.equal(fits, true, `Image must fit without cropping or upscaling at ${width}x${height}`);
	}
	await key('Escape');
	console.log('PASS: full-size lazy loading, isolated groups, singles, keyboard/Escape/focus, touch, errors, request races, dynamic content, link exclusions and responsive image bounds.');
} finally {
	if (ws?.readyState === WebSocket.OPEN) { await call('Browser.close').catch(() => {}); ws.close(); }
	browser.kill();
	server.closeAllConnections();
	await new Promise(done => server.close(done));
	// Delete only the exact, uniquely-created review profile inside the OS temp folder.
	if (directory.startsWith(resolve(tmpdir()) + sep) && basename(directory).startsWith('wp-lightbox-browser-')) {
		await rm(directory, {recursive:true, force:true, maxRetries:10, retryDelay:100}).catch(() => {});
	}
}
