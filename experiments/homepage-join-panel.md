# Homepage join panel experiment

The homepage now leads with a centred image and join panel, followed by three alternating contour action rows and the existing featured library content. Both the front-end renderer and WordPress editing preview use the new layout. Legacy feature-row CSS and existing feature metadata remain available.

Change the image under Pages → Home → Featured image. Local Home (ID 10) now uses existing Media Library image 36 (image 6), a workshop photograph. Home previously had no featured image. Images are database content and are not included in theme exports; select a featured image on hosting before deploying this experiment there.

To reverse only this experiment, preserving changes made before it:

    git apply -R --check experiments/homepage-join-panel.patch
    git apply -R experiments/homepage-join-panel.patch
    docker compose run --rm cli post meta delete 10 _thumbnail_id

The patch captures this experiment only, against the already-modified working files. If these areas change further, review patch conflicts rather than overwriting files. No broad code cleanup or export/version bump was performed.

Checked in Edge at 1440px, 900px and 390px: image loads, all three actions render, and no page-level horizontal overflow. PHP and editor JavaScript syntax checks pass.

Revision: join panel width is retained, while its height now follows the copy rather than a square image. Secondary actions reuse the pre-experiment feature-row layout, typography, spacing, contour sizing and responsive rules. Explicit row modifiers preserve the previous Browse/Contribute direction after Join is moved out of that list. The added event row continues the alternating rhythm. The join background mixes 25% of the existing random hero accent with 75% canvas, without selecting a second random colour. Desktop join height is approximately 341px (previously 616px) at 1440px viewport width.

Spacing refinement: secondary rows now start with contour left/copy right and alternate. Row minimum height is reduced by 10%, desktop row padding by 15%, small-screen padding by 10%, and the list bottom padding by 35%. The existing middle-row offset follows Contribute. Join-panel spacing is unchanged.

Hero alignment refinement: use flex-end above the existing 48rem stacking breakpoint and centre below it. Compact hero bottom spacing uses the shared L token; join-to-feature spacing uses M instead of full section spacing. Checked 1440, 900, 768, 767 and 390px widths; no horizontal overflow.

## Upcoming event carousel

The homepage selects all published events starting between now and seven days ahead, using the WordPress site timezone. Events appear soonest first, followed by Join the network. Without a qualifying event, only the join panel appears, with no controls. Existing event Featured image is used; missing photos show a decorative contour on mist. The date, location, format tag, linked title and Details action reuse event timeline classes. The softened random hero colour remains shared with Join.

Labelled dot buttons reuse the shared library-carousel dot class and switch slides manually; arrows/Home/End also work while a dot is focused. There is no auto-advance. Inactive slides are inert and hidden from assistive technology; transitions slide horizontally and respect reduced motion; the tallest slide determines panel height to avoid movement during switching. Without JavaScript both highlights remain readable. The editor continues to edit Join and explains that upcoming events are inserted automatically on the public homepage.

Checks: PHP/JavaScript syntax; Edge at 1440, 900 and 390px; click and keyboard switching, stable height, no script errors or horizontal overflow. Integration cases cover no event, past, inside/outside the seven-day window, invalid date, and missing-image fallback without changing database records.

    docker compose run --rm cli eval-file /var/www/html/wp-content/plugins/lgsdn-core/tests/homepage-spotlight.php

The event copy order is an uppercase calendar countdown (Today, Tomorrow, In N days), title, date/location, then Details. Countdown typography uses the article “Learn more about” size token. Countdown days compare local calendar dates, independent of daylight-saving hour changes. Format badges overlap the image left edge using the shared timeline placement. Checks also cover multiple slides, countdowns, identical dot dimensions, overlap, animation and reduced motion at wide/intermediate/mobile widths.

Mobile alignment refinement: below the existing 48rem breakpoint, spotlight copy starts at the top of its grid area. The image row retains its natural aspect-ratio height, with remaining shared carousel height reserved beneath the copy. All slides now start copy 16–23px below the image across tested mobile widths, while desktop remains centred.

Badge clearance: slide gap uses the shared S spacing token, exceeding the combined viewport padding and badge overhang. Track translation uses the same gap variable. Verified every slide at 1440, 900 and 390px: adjacent badges remain outside the viewport, active slide alignment is retained, and no horizontal page overflow occurs.

Event summaries: display the first sentence of the editorial excerpt, falling back to the event description, capped at 40 words. Strip markup/shortcodes and omit empty summaries. Place between date/location and Details. Verified current summaries and no overflow at 1440 and 390px.

Interaction and presentation refinement: pointer gestures support touch swiping and mouse dragging with live movement, direction locking, edge resistance, cancellation and drag-click suppression. Vertical touch scrolling and pinch zoom remain available. Countdown labels use ink/white rectangular badges. Summaries now aim for 180 characters, finishing the sentence, with a 360-character fallback cap, replacing the earlier first-sentence rule. Verified mouse dragging and emulated touch swipes in both directions.

Feature contour refinement: each coloured compound filled ring is replaced with its outer contour as a one-pixel non-scaling stroke. Existing darkened random ring colour, surrounding contours, marker dots and SVG dimensions remain unchanged. All three assets were checked together.
