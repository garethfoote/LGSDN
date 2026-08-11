(() => {
	const form = document.querySelector("[data-playbook-filter-form]");
	const results = document.querySelector("[data-playbook-results]");
	const activeFilters = document.querySelector("[data-active-filters]");
	const status = document.querySelector("[data-filter-status]");

	if (!form || !results || !activeFilters || !status || !window.fetch || !window.DOMParser) {
		return;
	}

	document.documentElement.classList.add("lgsdn-filters-enhanced");

	const facets = Array.from(form.querySelectorAll("[data-filter-facet]"));
	let requestController;

	const updateFacetCounts = () => {
		facets.forEach((facet) => {
			const count = facet.querySelectorAll('input[type="checkbox"]:checked').length;
			const output = facet.querySelector("[data-facet-count]");

			if (output) {
				output.textContent = count ? `${count} selected` : "";
			}
		});
	};

	const formUrl = () => {
		const parameters = new URLSearchParams(new FormData(form));
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

	facets.forEach((facet) => {
		const search = facet.querySelector("[data-facet-search]");
		const input = facet.querySelector("[data-facet-search-input]");
		const optionContainer = facet.querySelector("[data-facet-options]");
		const searchStatus = facet.querySelector("[data-facet-search-status]");
		const facetName = facet.dataset.filterFacet || "filter";

		if (search && input && optionContainer && searchStatus) {
			search.hidden = false;
			input.addEventListener("input", () => {
				const query = input.value.trim().toLocaleLowerCase();
				const options = Array.from(optionContainer.querySelectorAll("[data-filter-option]"));
				let visibleCount = 0;

				options.forEach((option) => {
					const matches = !query || option.dataset.filterOption.includes(query);
					option.hidden = !matches;
					if (matches) {
						visibleCount += 1;
					}
				});

				searchStatus.textContent = `${visibleCount} ${facetName} ${
					visibleCount === 1 ? "option" : "options"
				} shown.`;
			});
		}

		facet.addEventListener("toggle", () => {
			if (!facet.open) {
				return;
			}

			facets.forEach((otherFacet) => {
				if (otherFacet !== facet) {
					otherFacet.open = false;
				}
			});
		});
	});

	document.addEventListener("click", (event) => {
		facets.forEach((facet) => {
			if (facet.open && !facet.contains(event.target)) {
				facet.open = false;
			}
		});
	});

	document.addEventListener("keydown", (event) => {
		if (event.key !== "Escape") {
			return;
		}

		const openFacet = facets.find((facet) => facet.open);
		if (openFacet) {
			openFacet.open = false;
			openFacet.querySelector("summary")?.focus();
		}
	});

	form.addEventListener("change", (event) => {
		if (event.target.matches('input[type="checkbox"], select[name="sort"]')) {
			updateFacetCounts();
			updateResults();
		}
	});

	form.addEventListener("submit", (event) => {
		event.preventDefault();
		updateResults();
	});

	document.querySelector(".lgsdn-playbook-browse")?.addEventListener("click", (event) => {
		const removeLink = event.target.closest("[data-remove-filter]");
		const clearLink = event.target.closest("[data-clear-filters]");

		if (removeLink) {
			event.preventDefault();
			const checkbox = Array.from(form.querySelectorAll('input[type="checkbox"]')).find(
				(input) =>
					input.name === `${removeLink.dataset.removeFilter}[]` &&
					input.value === removeLink.dataset.removeValue
			);

			if (checkbox) {
				checkbox.checked = false;
				updateFacetCounts();
				updateResults();
			}
		}

		if (clearLink) {
			event.preventDefault();
			form.querySelectorAll('input[type="checkbox"]:checked').forEach((checkbox) => {
				checkbox.checked = false;
			});
			updateFacetCounts();
			updateResults();
		}
	});

	window.addEventListener("popstate", () => window.location.reload());
	updateFacetCounts();
})();
