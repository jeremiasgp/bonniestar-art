/* Native WordPress media library; no frontend assets or external dependencies. */
(() => {
	'use strict';
	const labels = window.artworkEditorLabels;
	if (!labels || !window.wp?.media) return;
	function initialize() {
		const root = document.querySelector('.artwork-gallery-editor');
		if (!root || root.dataset.ready) return;
		root.dataset.ready = 'true';
		const input = root.querySelector('input[name="artwork_gallery"]');
		const list = root.querySelector('ol');
		const status = root.querySelector('[role="status"]');
		function update() {
			const rows = [...list.children];
			input.value = rows.map(row => row.dataset.id).join(',');
			rows.forEach((row, index) => {
				row.querySelector('[data-action="up"]').disabled = index === 0;
				row.querySelector('[data-action="down"]').disabled = index === rows.length - 1;
			});
		}
		function rowFor(attachment) {
			const row = document.createElement('li');
			row.dataset.id = String(attachment.id);
			const image = document.createElement('img');
			image.src = attachment.sizes?.thumbnail?.url || attachment.url;
			image.alt = attachment.alt || '';
			const name = document.createElement('span');
			name.textContent = attachment.title || String(attachment.id);
			row.append(image, name);
			for (const action of ['up', 'down', 'remove']) {
				const button = document.createElement('button');
				button.type = 'button';
				button.className = 'button';
				button.dataset.action = action;
				button.textContent = labels[action];
				row.append(button);
			}
			return row;
		}
		root.querySelector('.artwork-gallery-select').addEventListener('click', () => {
			const frame = wp.media({title:labels.title, button:{text:labels.select}, library:{type:'image'}, multiple:true});
			frame.on('open', () => {
				const selection = frame.state().get('selection');
				for (const id of input.value.split(',').filter(Boolean)) {
					const attachment = wp.media.attachment(Number(id));
					attachment.fetch();
					selection.add(attachment);
				}
			});
			frame.on('select', async () => {
				try {
					const attachments = frame.state().get('selection').models;
					await Promise.all(attachments.map(model => model.get('url') ? Promise.resolve() : model.fetch()));
					list.replaceChildren(...attachments.map(model => rowFor(model.toJSON())));
					update(); status.textContent = labels.saved;
				} catch { status.textContent = labels.error; }
			});
			frame.open();
		});
		list.addEventListener('click', event => {
			const button = event.target.closest('button[data-action]');
			if (!button) return;
			const row = button.closest('li');
			const neighbor = row.nextElementSibling || row.previousElementSibling;
			if (button.dataset.action === 'up' && row.previousElementSibling) row.previousElementSibling.before(row);
			if (button.dataset.action === 'down' && row.nextElementSibling) row.nextElementSibling.after(row);
			if (button.dataset.action === 'remove') {
				row.remove();
				(neighbor?.querySelector('[data-action="remove"]') || root.querySelector('.artwork-gallery-select')).focus();
			}
			update(); status.textContent = labels.saved;
			if (button.dataset.action !== 'remove') {
				(button.disabled ? row.querySelector('[data-action="remove"]') : button).focus();
			}
		});
		update();
	}
	initialize();
	new MutationObserver(initialize).observe(document.body, {childList:true, subtree:true});
})();
