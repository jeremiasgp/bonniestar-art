/* Reusable frontend lightbox. No theme selectors, jQuery or third-party libraries. */
(() => {
	'use strict';
	if (!('HTMLDialogElement' in window) || !HTMLDialogElement.prototype.showModal) return;
	const labels = window.wpGalleryLightbox;
	if (!labels) return;
	const imageSelector = '.wp-block-image img, .gallery img, [data-gallery-lightbox] img';
	const groupSelector = '.wp-block-gallery, .gallery, [data-gallery-lightbox]';
	const records = new WeakMap();
	let dialog, stage, picture, caption, status, counter, previous, next, close, original;
	let items = [], index = 0, opener, request = 0, touch = null;
	let oldOverflow = '';
	let sessionActive = false;

	function safeURL(value) {
		if (!value) return null;
		try {
			const url = new URL(value, document.baseURI);
			return ['http:', 'https:'].includes(url.protocol) ? url.href : null;
		} catch { return null; }
	}

	function sourceFor(image) {
		const declared = safeURL(image.dataset.galleryLightboxSrc || image.dataset.fullUrl);
		const link = image.closest('a');
		const href = link && safeURL(link.getAttribute('href'));
		// Preserve links to pages, attachments, shops, downloads, etc.
		if (link && (link.hasAttribute('download') || !href ||
			(href !== declared && !/\.(?:avif|gif|jpe?g|png|webp|svg)$/i.test(new URL(href).pathname)))) return null;
		if (declared) return declared;
		if (href) return href;
		// For external/unmanaged images, use the largest width/density candidate.
		const candidates = (image.getAttribute('srcset') || '').split(',').map(candidate => {
			const match = candidate.trim().match(/^(\S+)\s+(\d+(?:\.\d+)?)[wx]$/);
			return match ? {url: safeURL(match[1]), width: Number(match[2])} : null;
		}).filter(candidate => candidate?.url).sort((a, b) => b.width - a.width);
		return candidates[0]?.url || safeURL(image.currentSrc || image.getAttribute('src'));
	}

	function enhance(root) {
		if (!(root instanceof Element) && root !== document) return;
		const images = [...root.querySelectorAll(imageSelector)];
		if (root.matches?.(imageSelector)) images.unshift(root);
		for (const image of images) {
			if (records.has(image) || image.closest('.no-lightbox, [data-no-lightbox], .wp-gallery-lightbox, button')) continue;
			const src = sourceFor(image);
			if (!src) continue;
			let trigger = image.closest('a');
			if (!trigger) {
				trigger = document.createElement('button');
				trigger.type = 'button';
				trigger.className = 'wp-gallery-lightbox-trigger';
				image.before(trigger);
				trigger.append(image);
			}
			trigger.setAttribute('aria-haspopup', 'dialog');
			trigger.setAttribute('aria-label', image.alt ? `${labels.open}: ${image.alt}` : labels.open);
			const item = {image, trigger, src};
			records.set(image, item);
			trigger.addEventListener('click', event => {
				if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button > 0) return;
				event.preventDefault();
				const group = image.closest(groupSelector);
				items = group ? [...group.querySelectorAll('img')]
					.filter(candidate => candidate.closest(groupSelector) === group)
					.map(candidate => records.get(candidate)).filter(Boolean) : [item];
				opener = trigger;
				buildDialog();
				oldOverflow = document.documentElement.style.overflow;
				sessionActive = true;
				document.documentElement.style.overflow = 'hidden';
				dialog.showModal();
				close.focus();
				show(items.indexOf(item));
			});
		}
	}

	function element(tag, className, text) {
		const node = document.createElement(tag);
		node.className = className;
		if (text) node.textContent = text;
		return node;
	}

	function control(label, symbol) {
		const button = element('button', 'wp-gallery-lightbox__control');
		button.type = 'button';
		button.setAttribute('aria-label', label);
		const icon = element('span', '', symbol);
		icon.setAttribute('aria-hidden', 'true');
		button.append(icon);
		return button;
	}

	function buildDialog() {
		if (dialog) return;
		dialog = element('dialog', 'wp-gallery-lightbox');
		dialog.setAttribute('aria-label', labels.title);
		const toolbar = element('div', 'wp-gallery-lightbox__toolbar');
		counter = element('span', 'wp-gallery-lightbox__counter');
		const controls = element('div', 'wp-gallery-lightbox__controls');
		previous = control(labels.previous, '←');
		next = control(labels.next, '→');
		close = control(labels.close, '×');
		controls.append(previous, next, close);
		toolbar.append(counter, controls);
		stage = element('div', 'wp-gallery-lightbox__stage');
		picture = element('img', 'wp-gallery-lightbox__image');
		picture.alt = '';
		picture.draggable = false;
		picture.decoding = 'async';
		status = element('p', 'wp-gallery-lightbox__status');
		status.setAttribute('role', 'status');
		status.setAttribute('aria-live', 'polite');
		stage.append(picture, status);
		const footer = element('div', 'wp-gallery-lightbox__footer');
		caption = element('p', 'wp-gallery-lightbox__caption');
		original = element('a', 'wp-gallery-lightbox__original', labels.original);
		footer.append(caption, original);
		dialog.append(toolbar, stage, footer);
		document.body.append(dialog);
		previous.addEventListener('click', () => show(index - 1));
		next.addEventListener('click', () => show(index + 1));
		close.addEventListener('click', dismiss);
		dialog.addEventListener('cancel', event => { event.preventDefault(); dismiss(); });
		dialog.addEventListener('close', finishClose);
		dialog.addEventListener('keydown', event => {
			if (event.key === 'Tab') {
				const focusable = [...dialog.querySelectorAll('button:not([disabled]), a[href]')]
					.filter(node => node.getClientRects().length);
				const first = focusable[0], last = focusable.at(-1);
				if (event.shiftKey && document.activeElement === first) {
					event.preventDefault(); last.focus();
				} else if (!event.shiftKey && document.activeElement === last) {
					event.preventDefault(); first.focus();
				}
				return;
			}
			if (event.ctrlKey || event.metaKey || event.altKey || items.length < 2) return;
			const actions = {ArrowLeft: () => show(index - 1), ArrowRight: () => show(index + 1), Home: () => show(0), End: () => show(items.length - 1)};
			if (actions[event.key]) { event.preventDefault(); actions[event.key](); }
		});
		stage.addEventListener('pointerdown', event => {
			touch = event.pointerType === 'touch' && event.isPrimary ? {x: event.clientX, y: event.clientY, id: event.pointerId} : null;
		});
		stage.addEventListener('pointercancel', () => { touch = null; });
		stage.addEventListener('pointerup', event => {
			if (!touch || event.pointerId !== touch.id) return;
			const dx = event.clientX - touch.x, dy = event.clientY - touch.y;
			touch = null;
			if (items.length > 1 && Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy) * 1.5) show(index + (dx < 0 ? 1 : -1));
		});
	}

	function finishClose() {
		// A queued native close event may arrive after a new image has been opened.
		if (dialog.open || !sessionActive) return;
		sessionActive = false;
		request++;
		touch = null;
		picture.removeAttribute('src');
		picture.hidden = true;
		document.documentElement.style.overflow = oldOverflow;
		if (opener?.isConnected) opener.focus({preventScroll: true});
	}

	function dismiss() {
		dialog.close();
		finishClose();
	}

	async function show(position) {
		index = (position + items.length) % items.length;
		const item = items[index], token = ++request;
		const count = labels.position.replace('%1$s', String(index + 1)).replace('%2$s', String(items.length));
		counter.textContent = count;
		previous.hidden = next.hidden = items.length < 2;
		caption.textContent = item.image.closest('figure')?.querySelector('figcaption')?.textContent.trim() || '';
		caption.hidden = !caption.textContent;
		original.href = item.src;
		original.hidden = true;
		picture.hidden = true;
		picture.removeAttribute('src');
		picture.alt = item.image.alt;
		stage.setAttribute('aria-busy', 'true');
		status.classList.remove('wp-gallery-lightbox__status--loaded');
		status.textContent = labels.loading;
		const full = new Image();
		try {
			await new Promise((resolve, reject) => { full.onload = resolve; full.onerror = reject; full.src = item.src; });
			if (full.decode) await full.decode();
			if (token !== request || !dialog.open) return;
			picture.src = item.src;
			picture.hidden = false;
			status.textContent = count;
			status.classList.add('wp-gallery-lightbox__status--loaded');
		} catch {
			if (token !== request || !dialog.open) return;
			status.textContent = labels.error;
			original.hidden = false;
		} finally {
			if (token === request) stage.setAttribute('aria-busy', 'false');
		}
		// Keep loading/errors visible, but announce successful changes without a visual overlay.
		if (picture.hidden) status.classList.remove('wp-gallery-lightbox__status--loaded');
	}

	enhance(document);
	new MutationObserver(changes => {
		for (const change of changes) for (const node of change.addedNodes) enhance(node);
	}).observe(document.body, {childList: true, subtree: true});
})();
