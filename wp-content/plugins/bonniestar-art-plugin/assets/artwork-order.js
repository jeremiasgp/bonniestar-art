/* Native pointer and keyboard ordering. No external libraries. */
(() => {
	'use strict';
	const root = document.querySelector('.artwork-order');
	const config = window.bonniestarArtworkOrder;
	if (!root || !config) return;
	const list = root.querySelector('.artwork-order-list');
	const save = root.querySelector('.artwork-order-save');
	const status = root.querySelector('.artwork-order-status');
	const ids = () => [...list.children].map(item => Number(item.dataset.id));
	let baseline = JSON.stringify(ids());
	let state = root.dataset.state;
	let saving = false, dirty = false, drag = null, frame = 0;

	function update(message) {
		dirty = JSON.stringify(ids()) !== baseline;
		save.disabled = saving || !dirty;
		const items = [...list.children];
		for (const [index, item] of items.entries()) {
			item.querySelector('.artwork-order-handle').disabled = saving;
			item.querySelector('[data-move="-1"]').disabled = saving || index === 0;
			item.querySelector('[data-move="1"]').disabled = saving || index === items.length - 1;
		}
		status.textContent = message ?? (dirty ? config.changed : '');
	}

	function move(item, direction, control) {
		if (saving || drag) return;
		const neighbor = direction < 0 ? item.previousElementSibling : item.nextElementSibling;
		if (!neighbor) return;
		if (direction < 0) neighbor.before(item);
		else neighbor.after(item);
		update();
		// At a boundary the clicked button becomes disabled; keep a usable focus.
		(control.disabled ? item.querySelector('.artwork-order-handle') : control).focus({preventScroll: true});
		item.scrollIntoView({block: 'nearest'});
	}

	list.addEventListener('click', event => {
		const button = event.target.closest('[data-move]');
		if (button) move(button.closest('[data-id]'), Number(button.dataset.move), button);
	});
	list.addEventListener('keydown', event => {
		const handle = event.target.closest('.artwork-order-handle');
		if (!handle || !['ArrowUp', 'ArrowDown'].includes(event.key)) return;
		event.preventDefault();
		move(handle.closest('[data-id]'), event.key === 'ArrowUp' ? -1 : 1, handle);
	});

	function place(y, x) {
		const target = document.elementFromPoint(x, y)?.closest('.artwork-order-item');
		if (!target || target === drag.item || target.parentElement !== list) return;
		const bounds = target.getBoundingClientRect();
		if (y < bounds.top + bounds.height / 2) target.before(drag.item);
		else target.after(drag.item);
	}

	function autoScroll() {
		if (!drag) return;
		const speed = drag.y < 90 ? -12 : drag.y > innerHeight - 70 ? 12 : 0;
		if (speed) {
			window.scrollBy(0, speed);
			place(drag.y, drag.x);
		}
		frame = requestAnimationFrame(autoScroll);
	}

	list.addEventListener('pointerdown', event => {
		const handle = event.target.closest('.artwork-order-handle');
		if (!handle || saving || drag || !event.isPrimary || event.button !== 0) return;
		event.preventDefault();
		handle.focus({preventScroll: true});
		drag = {id: event.pointerId, item: handle.closest('[data-id]'), original: [...list.children], x: event.clientX, y: event.clientY};
		drag.item.classList.add('is-dragging');
		list.classList.add('is-dragging');
		autoScroll();
	});
	document.addEventListener('pointermove', event => {
		if (!drag || event.pointerId !== drag.id) return;
		drag.x = event.clientX;
		drag.y = event.clientY;
		place(drag.y, drag.x);
	});
	function finish(event, cancel = false) {
		if (!drag || event.pointerId !== drag.id) return;
		const handle = drag.item.querySelector('.artwork-order-handle');
		if (cancel) list.replaceChildren(...drag.original);
		drag.item.classList.remove('is-dragging');
		list.classList.remove('is-dragging');
		drag = null;
		cancelAnimationFrame(frame);
		update();
		handle.focus({preventScroll: true});
	}
	document.addEventListener('pointerup', event => finish(event));
	document.addEventListener('pointercancel', event => finish(event, true));
	window.addEventListener('blur', () => { if (drag) finish({pointerId: drag.id}, true); });
	document.addEventListener('keydown', event => {
		if (drag && event.key === 'Escape') { event.preventDefault(); finish({pointerId: drag.id}, true); }
	});

	save.addEventListener('click', async () => {
		if (saving || drag || !dirty) return;
		saving = true;
		root.setAttribute('aria-busy', 'true');
		update(config.saving);
		try {
			const response = await fetch(config.url, {
				method: 'POST', credentials: 'same-origin',
				body: new URLSearchParams({action: 'bonniestar_art_save_order', nonce: config.nonce, state, order: JSON.stringify(ids())}),
			});
			const result = await response.json().catch(() => null);
			if (!response.ok || !result?.success) throw new Error(result?.data?.message || config.error);
			state = result.data.state;
			baseline = JSON.stringify(ids());
			saving = false;
			update(config.saved);
		} catch (error) {
			saving = false;
			update(error instanceof TypeError ? config.error : error.message || config.error);
		} finally {
			root.removeAttribute('aria-busy');
		}
	});
	window.addEventListener('beforeunload', event => {
		if (dirty) { event.preventDefault(); event.returnValue = ''; }
	});
	update();
})();
