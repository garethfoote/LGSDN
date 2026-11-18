/* A preference cookie is only written when someone makes a choice. */
(() => {
	const panel = document.querySelector('.lgsdn-analytics-choice');
	if (!panel) return;
	const status = panel.querySelector('[data-analytics-status]');
	const stop = panel.querySelector('[data-analytics-opt-out]');
	const allow = panel.querySelector('[data-analytics-opt-in]');
	const browserSignal = navigator.doNotTrack === '1' || navigator.globalPrivacyControl === true;
	const refresh = () => {
		const excluded = document.cookie.split(';').some(cookie => cookie.trim() === 'lgsdn_analytics_opt_out=1');
		status.textContent = browserSignal
			? 'Your browser sends a privacy signal. Requests carrying this signal are excluded from analytics.'
			: excluded ? 'Your visits from this browser are excluded from analytics.' : 'Your visits from this browser can be included in analytics.';
		stop.hidden = browserSignal || excluded;
		allow.hidden = browserSignal || !excluded;
	};
	stop.addEventListener('click', () => {
		document.cookie = 'lgsdn_analytics_opt_out=1; Path=/; Max-Age=31536000; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
		refresh();
	});
	allow.addEventListener('click', () => {
		document.cookie = 'lgsdn_analytics_opt_out=; Path=/; Max-Age=0; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
		refresh();
	});
	refresh();
})();
