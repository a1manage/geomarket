(function () {
	function init() {
		const wrapper = document.querySelector('.back-to-top-wrapper');
		if (!wrapper) return;

		wrapper.classList.add('a1geo-paper-back-to-top');
		function updateVisibility() {
			wrapper.hidden = window.scrollY < 400;
		}
		updateVisibility();
		window.addEventListener('scroll', updateVisibility, { passive: true });
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
}());
