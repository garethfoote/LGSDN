# LGSDN website

The WordPress website for the Local Government Service Design Network.

## Architecture

- `wp-content/plugins/lgsdn-core` owns structured content, metadata, taxonomies, and editor safeguards.
- `wp-content/themes/lgsdn` owns templates, patterns, design tokens, and presentation.
- WordPress core and the database are local Docker services and are not committed.

The deployed site has no JavaScript package runtime and does not require a page-builder or custom-fields plugin.

## Run locally

1. Copy `.env.example` to `.env` if you want to change the defaults.
2. Run `docker compose up -d`.
3. Open <http://localhost:8080> and complete the WordPress installer.
4. Activate **LGSDN Core** and the **LGSDN** theme.
5. Set permalinks to **Post name**.

The plugin creates the controlled Playbook terms when it is activated. Councils are deliberately managed through WordPress as the network grows.

## Design system

The theme includes a responsive specimen template for reviewing typography, colour, spacing, layout, interface elements, and contour artwork. In the local development database it is available at <http://localhost:8080/design-system/>.

See [`DESIGN-SYSTEM.md`](DESIGN-SYSTEM.md) for the editable token locations and breakpoint rationale. The type and spacing scales live in `wp-content/themes/lgsdn/theme.json`; responsive component rules live in `wp-content/themes/lgsdn/style.css`.

## Content model

- **Playbook items**: structured examples classified by Service, Practice, Council, Purpose, and Challenge.
- **People**: name, biography, image, role, organisation, and optional profile link.
- **Events**: description, date range, location, format, and optional booking link.
- **Pages**: Home, Join, Network, Playbook, and ordinary information pages.

Service, Practice, Purpose, and Challenge are controlled vocabularies: editors can assign terms but only administrators can change the vocabulary. Editors may add and manage Council terms.

Each Playbook item has a public **Case study author** selected from People and may have several Practice terms. Editors select one assigned term as the **Primary practice**; that term controls the card colour and fallback contour. Practice, Service, Challenge, Case study author, and Primary practice must be complete before an item can be published through the block editor.

Administrators manage the approved colour for each term under **Playbook items → Practices**. The colour control is intentionally limited to the design-system palette rather than accepting arbitrary values. Each colour’s fallback contour is a theme asset named `practice-contour-{colour}.svg`; WordPress displays an administrator notice if a palette colour does not have a matching file.

## Editing service area pages

Open **Playbook items → Service areas**, then edit a service area. The **Introduction** rich-text field controls the short article-style text above its case studies. It supports paragraphs, headings, links, and lists. If it is blank, the page uses the existing **Description**; if both are blank, the introduction is omitted. For a new service area, create it first and then open its edit screen to add an introduction.

The page uses the service area's name, colour, and icon, followed by shared Playbook cards for all published items assigned to that area, including secondary assignments. Items appear newest first, without pagination. Service area editing retains the existing administrator permissions.

## Editing the homepage

Edit **Pages → Home** and use the **Homepage content** panel for the lead copy and the three feature cards. Each card has an editable title, body, and destination page. The featured Playbook item remains static in the homepage renderer for now.

Homepage events come from **Events**. The **Event details** panel provides start and end dates, location, format, and an optional booking link. The homepage selects the next five valid upcoming events, displays them in descending date order, and appends the most recent past event. Month/year labels and weekdays come from the start date in the WordPress site timezone. An event becomes past when its start time passes. Missing or invalid start dates are omitted.

Use the standard WordPress content editor on an event to describe what it is. This article body appears on the event's detail page and supports the usual blocks, including paragraphs, headings, links, lists, and images.

The Events archive at `/events/` reuses the timeline for every valid upcoming and past event. Its rows have increased vertical spacing and larger photos while the homepage keeps the compact curated variant.

Upcoming events with a booking link display **Register** with an external-link icon and link directly to the registration site. Otherwise the action is **Details**, linking to the event page; titles always link to that page. Past events have a **Past** badge and a dashed timeline rail. The shared timeline retains its month gutter on small screens, with actions below titles until the wide breakpoint.

Add an event photo using **Featured image** in the event editor. The timeline shows a rounded photo, up to 128px wide on the homepage and 192px wide on the Events archive, that stretches alongside the full event content at narrower widths. When the event has a format, the photo carries a black **In person**, **Online**, or **Hybrid** tag at its top-left edge. Past-event photos are faded slightly while their format tags remain fully opaque. Events without a featured image use the available space for their text, with no placeholder or format tag; for these events, the format remains the fallback location text.
