(() => {
	const form = document.querySelector("[data-playbook-filter-form]");
	const results = document.querySelector("[data-playbook-results]");
	const activeFilters = document.querySelector("[data-active-filters]");
	const status = document.querySelector("[data-filter-status]");

	if (!form || !results || !activeFilters || !status || !window.fetch || !window.DOMParser) {
		return;
	}

	document.documentElement.classList.add("lgsdn-filters-enhanced");

	const tabs = Array.from(document.querySelectorAll("[data-playbook-tab]"));
	const panels = Array.from(document.querySelectorAll("[data-playbook-panel]"));
	const headings = Array.from(document.querySelectorAll("[data-playbook-heading]"));
	const practicePanelHeading = document.querySelector(".lgsdn-practice-panel__heading");
	const selectPanel = (name, updateHash = true) => {
		tabs.forEach((tab) => {
			tab.hidden = tab.dataset.playbookTab === name;
		});
		headings.forEach((heading) => {
			heading.hidden = heading.dataset.playbookHeading !== name;
		});
		panels.forEach((panel) => {
			panel.hidden = panel.dataset.playbookPanel !== name;
		});
		if (practicePanelHeading) {
			practicePanelHeading.hidden = true;
		}
		if (updateHash) {
			window.history.replaceState({}, "", `#${name === "practices" ? "practice-panel" : "case-study-panel"}`);
		}

		if (updateHash) {
			document.querySelector(`[data-playbook-heading="${name}"]`)?.focus();
		}
	};

	tabs.forEach((tab) => {
		tab.addEventListener("click", (event) => {
			selectPanel(tab.dataset.playbookTab);
		});
	});

	const initialPanel = window.location.hash === "#practice-panel" ? "practices" : "case-studies";
	selectPanel(initialPanel, false);

	const facets = Array.from(form.querySelectorAll("[data-filter-facet]"));
	let requestController;

	const updateFacetStates = () => {
		facets.forEach((facet) => {
			const select = facet.querySelector("[data-filter-select]");
			const clearButton = facet.querySelector("[data-clear-facet]");

			if (!select || !clearButton) {
				return;
			}

			const hasSelection = Boolean(select.value);
			facet.classList.toggle("has-selection", hasSelection);
			clearButton.hidden = !hasSelection;
		});
	};

	const formUrl = () => {
		const parameters = new URLSearchParams();
		new FormData(form).forEach((value, key) => {
			if (value) {
				parameters.append(key, value);
			}
		});
		return `${form.action}?${parameters.toString()}`;
	};

	const announce = (message) => {
		status.textContent = "";
		window.requestAnimationFrame(() => {
			status.textContent = message;
		});
	};

	const updateResults = async (url = formUrl()) => {
		if (requestController) {
			requestController.abort();
		}

		const controller = new AbortController();
		requestController = controller;
		results.setAttribute("aria-busy", "true");
		announce("Updating case studies.");

		try {
			const response = await fetch(url, {
				headers: {
					"X-Requested-With": "XMLHttpRequest",
				},
				signal: controller.signal,
			});

			if (!response.ok) {
				throw new Error(`Filter request failed with status ${response.status}`);
			}

			const page = new DOMParser().parseFromString(await response.text(), "text/html");
			const nextResults = page.querySelector("[data-playbook-results]");
			const nextActiveFilters = page.querySelector("[data-active-filters]");
			const nextStatus = page.querySelector("[data-filter-status]");

			if (!nextResults || !nextActiveFilters || !nextStatus) {
				throw new Error("The filter response did not contain the expected content.");
			}

			results.innerHTML = nextResults.innerHTML;
			activeFilters.innerHTML = nextActiveFilters.innerHTML;
			activeFilters.hidden = nextActiveFilters.hidden;
			window.history.replaceState({}, "", url);
			announce(nextStatus.textContent.trim());
		} catch (error) {
			if (error.name !== "AbortError") {
				document.documentElement.classList.remove("lgsdn-filters-enhanced");
				announce("Live filtering is unavailable. Use the Apply filters button.");
			}
		} finally {
			if (requestController === controller && !controller.signal.aborted) {
				results.setAttribute("aria-busy", "false");
			}
		}
	};

	form.addEventListener("change", (event) => {
		if (event.target.matches("select")) {
			if (event.target.matches("[data-filter-select]")) {
				updateFacetStates();
			}
			updateResults();
		}
	});

	form.addEventListener("submit", (event) => {
		event.preventDefault();
		updateResults();
	});

	document.querySelector(".lgsdn-playbook-browse")?.addEventListener("click", (event) => {
		const clearFacetButton = event.target.closest("[data-clear-facet]");
		const clearLink = event.target.closest("[data-clear-filters]");

		if (clearFacetButton) {
			event.preventDefault();
			event.stopPropagation();
			const facet = clearFacetButton.closest("[data-filter-facet]");
			const select = facet?.querySelector("[data-filter-select]");

			if (select) {
				select.value = "";
				updateFacetStates();
				updateResults();
			}
			return;
		}

		if (clearLink) {
			event.preventDefault();
			facets.forEach((facet) => {
				const select = facet.querySelector("select");
				if (select) {
					select.value = "";
				}
			});
			updateFacetStates();
			updateResults();
		}
	});

	window.addEventListener("popstate", () => window.location.reload());
	updateFacetStates();
})();
