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

The local WordPress container loads `docker/php/uploads.ini`, allowing individual uploads up to 32 MB with a 40 MB request limit. Production hosting needs equivalent PHP limits configured separately.

## Exporting for WordPress

From the project root, run this cross-platform command (Windows, macOS, or Linux):

```sh
python scripts/export-wordpress.py
```

You can optionally provide a different output directory as the first argument.

This creates upload-ready archives at `wp-content/themes/lgsdn-theme.zip` and `wp-content/plugins/lgsdn-core.zip`. The theme and plugin folders are included at the archive root, as required by WordPress. Plugin tests are left out of the production archive. The generated ZIPs are ignored by Git.

## Design system

The theme includes a responsive specimen template for reviewing typography, colour, spacing, layout, interface elements, and contour artwork. In the local development database it is available at <http://localhost:8080/design-system/>.

See [`DESIGN-SYSTEM.md`](DESIGN-SYSTEM.md) for the editable token locations and breakpoint rationale. The type and spacing scales live in `wp-content/themes/lgsdn/theme.json`; responsive component rules live in `wp-content/themes/lgsdn/style.css`.

## Content model

- **Playbook items**: structured examples classified by Service, Practice, Council, Purpose, and Challenge.
- **People**: name, biography, image, role, organisation, and optional profile link.
- **Events**: description, date range, location, optional map link, format, and an optional booking link with configurable call to action text.
- **Pages**: Home, Join, Network, Events, Playbook, and ordinary information pages.

Service, Practice, Purpose, and Challenge are controlled vocabularies: editors can assign terms but only administrators can change the vocabulary. Editors may add and manage Council terms.

Each Playbook item has a public **Case study author** selected from People and may have several Practice terms. Editors select one assigned term as the **Primary practice**; that term controls the card colour and fallback contour. Practice, Service, Challenge, Case study author, and Primary practice must be complete before an item can be published through the block editor.

Administrators manage the approved colour for each term under **Playbook items → Practices**. The colour control is intentionally limited to the design-system palette rather than accepting arbitrary values. Each colour’s fallback contour is a theme asset named `practice-contour-{colour}.svg`; WordPress displays an administrator notice if a palette colour does not have a matching file.

## Editing service area pages

Open **Playbook items → Service areas**, then edit a service area. The **Introduction** rich-text field controls the short article-style text above its case studies. It supports paragraphs, headings, links, and lists. If it is blank, the page uses the existing **Description**; if both are blank, the introduction is omitted. For a new service area, create it first and then open its edit screen to add an introduction.

The page uses the service area's name, colour, and icon, followed by shared Playbook cards for all published items assigned to that area, including secondary assignments. Items appear newest first, without pagination. Service area editing retains the existing administrator permissions.

## Editing the homepage

Edit **Pages → Home** and use the **Homepage content** panel for the lead copy and the three feature cards. Each card has an editable title, body, and destination page. The featured Playbook item remains static in the homepage renderer for now.

Homepage events come from **Events**. The **Event details** panel provides start and end dates, location, an optional map link, format, an optional booking link, optional call to action text, and a repeatable Resources control. Resource rows accept an external URL or a file selected or uploaded through the WordPress Media Library. A map link appears beneath the location on the event page. Complete resource label-and-link pairs appear in a callout before the main event copy. When call to action text is blank, the event page uses **Register**. The homepage selects the next five valid upcoming events, displays them in descending date order, and appends the most recent past event. Month/year labels and weekdays come from the start date in the WordPress site timezone. An event becomes past when its start time passes. Missing or invalid start dates are omitted.

Use the standard WordPress content editor on an event to describe what it is. This article body appears on the event's detail page and supports the usual blocks, including paragraphs, headings, links, lists, and images.

Edit **Pages → Join us at an event** to change the title and introductory content above the complete events timeline. The dedicated Events page template keeps the generated listing in place beneath that editable content. Individual events continue to be managed under **Events** and use URLs below `/events/`.

The Events page at `/events/` reuses the timeline for every valid upcoming and past event. Its rows have increased vertical spacing and larger photos while the homepage keeps the compact curated variant.

Every timeline action is **Details** and links to the event page; titles link there too. Upcoming event pages with a booking link display the configured call to action with an external-link icon. Past events hide that booking action and have a **Past** badge and a dashed timeline rail. The shared timeline retains its month gutter on small screens. At the compact breakpoint, every event keeps its action below the title; pictured events place both rows beneath the image. From the medium breakpoint, pictured events keep their content and action together beneath the image.

Add an event photo using **Featured image** in the event editor. Below the wide breakpoint, the timeline shows a rounded, full-width photo above the event content and action. On wide layouts, the photo sits beside the content at up to 128px wide on the homepage and 192px wide on the Events page. When an event has a format, the photo carries a black **In person**, **Online**, or **Hybrid** tag at its top-left edge. Past-event photos are faded slightly while their format tags remain fully opaque. Events without a featured image use the available space for their text, with no placeholder or format tag; for these events, the format remains the fallback location text.

## Editing the network page

Manage members under **People**. Add the person's name as the title, their full biography in the main editor, their role in **Person details**, their council in **Councils**, and their portrait using **Featured image**. The optional excerpt is used as the short biography on the Network page; when it is blank, the page creates a short version from the main biography.

The Network page lists every published person in menu order and then alphabetically. Portraits are circular with the shared ink stroke. Profiles without an uploaded portrait use `assets/images/person-fallback.png`. People do not have separate public detail pages, and cards do not link to authored articles yet.
