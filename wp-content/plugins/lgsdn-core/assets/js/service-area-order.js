(function ($) {
	'use strict';

	$(function () {
		var settings = window.LGSDNServiceAreaOrder || {};
		var $list = $('#the-list');
		var $rows = $list.children('tr[id^="tag-"]');
		var $status = $('<p id="lgsdn-service-order-status" role="status" aria-live="polite"></p>');
		var busy = false;

		if (!$list.length) {
			return;
		}

		$('.tablenav.top').after($status);

		function rowsInOrder() {
			return $list.children('tr[id^="tag-"]');
		}

		function termIds() {
			return rowsInOrder().map(function () {
				return Number(this.id.replace('tag-', ''));
			}).get();
		}

		function syncMoveButtons() {
			var $currentRows = rowsInOrder();
			$currentRows.each(function (index) {
				$(this).find('.lgsdn-service-order-up').prop('disabled', busy || index === 0);
				$(this).find('.lgsdn-service-order-down').prop('disabled', busy || index === $currentRows.length - 1);
				$(this).find('.lgsdn-service-order-handle').prop('disabled', busy);
			});
		}

		function restoreOrder(ids) {
			ids.forEach(function (id) {
				var row = document.getElementById('tag-' + id);
				if (row) {
					$list.append(row);
				}
			});
		}

		function saveOrder(previousIds) {
			busy = true;
			$list.sortable('disable');
			syncMoveButtons();

			$.post(settings.ajaxUrl, {
				action: 'lgsdn_reorder_service_areas',
				nonce: settings.nonce,
				term_ids: termIds()
			}).done(function (response) {
				if (response && response.success) {
					$status.text(response.data.message);
				} else {
					restoreOrder(previousIds);
					$status.text(response && response.data && response.data.message ? response.data.message : 'The Playbook order could not be saved.');
				}
			}).fail(function (xhr) {
				restoreOrder(previousIds);
				var message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message;
				$status.text(message || 'The Playbook order could not be saved. Try again.');
			}).always(function () {
				busy = false;
				$list.sortable('enable');
				syncMoveButtons();
			});
		}

		var canReorder = settings.enabled && $rows.length > 0 && $rows.length === Number(settings.termCount);
		if (!canReorder) {
			$list.find('.lgsdn-service-order-controls button').prop('disabled', true);
			$status.addClass('notice notice-info').text('To reorder, clear the search and sorting options and show all service areas on this page.');
			return;
		}

		$status.text('Drag service areas to set the Playbook order, or use the up and down buttons. Homepage order remains separate.');
		$list.sortable({
			axis: 'y',
			handle: '.lgsdn-service-order-handle',
			cancel: 'input, textarea, select, option, .lgsdn-service-order-up, .lgsdn-service-order-down',
			items: '> tr[id^="tag-"]',
			start: function () {
				$list.data('lgsdnPreviousOrder', termIds());
			},
			update: function () {
				saveOrder($list.data('lgsdnPreviousOrder') || []);
			}
		});

		$list.on('click', '.lgsdn-service-order-up, .lgsdn-service-order-down', function () {
			var previousIds = termIds();
			var $row = $(this).closest('tr');
			if ($(this).hasClass('lgsdn-service-order-up')) {
				$row.insertBefore($row.prevAll('tr[id^="tag-"]').first());
			} else {
				$row.insertAfter($row.nextAll('tr[id^="tag-"]').first());
			}
			syncMoveButtons();
			saveOrder(previousIds);
		});

		syncMoveButtons();
	});
})(jQuery);
