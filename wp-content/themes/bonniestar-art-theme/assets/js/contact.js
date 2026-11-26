/* Native popover: Escape, outside click and focus restoration are browser-owned. */
(() => {
	'use strict';
	const trigger = document.querySelector('.bonniestar-art-email-trigger');
	const panel = document.querySelector('#bonniestar-art-email-popover');
	if (!trigger || !panel) return;
	const address = panel.querySelector('.bonniestar-art-email-address');
	const copy = panel.querySelector('.bonniestar-art-email-copy');
	const status = panel.querySelector('[role="status"]');
	const messages = window.bonniestarContact;
	const copyLabel = copy.getAttribute('aria-label');
	let feedbackTimer;

	function resetFeedback() {
		clearTimeout(feedbackTimer);
		copy.classList.remove('is-copied');
		copy.setAttribute('aria-label', copyLabel);
	}

	function position() {
		if (!panel.matches(':popover-open')) return;
		const rect = trigger.getBoundingClientRect();
		const width = panel.getBoundingClientRect().width;
		const left = Math.max(8, Math.min(innerWidth - width - 8, rect.right - width));
		panel.style.left = `${left}px`;
		panel.style.top = `${Math.max(8, Math.min(innerHeight - panel.offsetHeight - 8, rect.bottom + 12))}px`;
		panel.style.setProperty('--email-arrow-left', `${Math.max(12, Math.min(width - 12, rect.left + rect.width / 2 - left))}px`);
	}
	if (!('showPopover' in panel)) {
		// A mail link still works on browsers without the Popover API.
		trigger.addEventListener('click', () => { window.location.href = address.href; });
		return;
	}
	panel.addEventListener('toggle', () => { resetFeedback(); status.textContent = ''; position(); });
	window.addEventListener('resize', position, {passive: true});
	window.addEventListener('scroll', position, {passive: true});
	if (!navigator.clipboard?.writeText || !messages) return;
	copy.hidden = false;
	copy.addEventListener('click', async () => {
		try {
			await navigator.clipboard.writeText(address.textContent.trim());
			status.textContent = '';
			clearTimeout(feedbackTimer);
			copy.classList.add('is-copied');
			copy.setAttribute('aria-label', messages.copied);
			feedbackTimer = setTimeout(resetFeedback, 300);
		} catch {
			resetFeedback();
			status.textContent = messages.failed;
			const selection = window.getSelection();
			const range = document.createRange();
			range.selectNodeContents(address);
			selection.removeAllRanges();
			selection.addRange(range);
		}
		position();
	});
})();
