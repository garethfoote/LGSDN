# LGSDN website

The WordPress website for the Local Government Service Design Network.

## Licensing

Original software is licensed under [GPL v2 or later](LICENSE). Original published network content uses CC BY 4.0 except where otherwise stated. Fonts, icons, logos and other third-party material retain separate terms: see [LICENSING.md](LICENSING.md) before reusing or redistributing assets.

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

Install and activate [Safe SVG](https://wordpress.org/plugins/safe-svg/) to upload SVG council logos. It sanitizes SVG uploads before they enter the Media Library; without it, use PNG or JPEG logos.

The plugin creates the controlled Playbook terms when it is activated. Editors can add Council terms as new councils contribute to the Playbook.

The local WordPress container loads `docker/php/uploads.ini`, allowing individual uploads up to 32 MB with a 40 MB request limit. Production hosting needs equivalent PHP limits configured separately.

## Exporting for WordPress

From the project root, run this cross-platform command (Windows, macOS, or Linux):

```sh
python scripts/export-wordpress.py
```

You can optionally provide a different output directory as the first argument.

This creates upload-ready archives at `wp-content/themes/lgsdn-theme.zip` and `wp-content/plugins/lgsdn-core.zip`. The theme and plugin folders are included at the archive root, as required by WordPress. Plugin tests are left out of the production archive. The generated ZIPs are local build outputs: they are ignored by Git and are not tracked in the repository. Rebuild them from the source before deployment.

Each export also increments the shared patch version (for example, `0.1.0` becomes `0.1.1`) in the theme and plugin headers before creating the archives.

The WordPress admin **Tools → Export** screen remains useful when you only need a portable XML export of selected content for importing into another WordPress installation. It does not include the complete database, users, settings, or all plugin data.

## Updating the live site

GitHub stores the source history; pushing a commit does not deploy WordPress.

1. Run `python scripts/export-wordpress.py` to build fresh theme and plugin ZIPs and increment their versions.
2. Commit the source and generated ZIPs, then push to GitHub.
3. Take a backup in Hostinger before updating the live installation.
4. In live WordPress admin, open **Appearance → Themes → Add theme → Upload theme**, select `wp-content/themes/lgsdn-theme.zip`, and choose **Replace current with uploaded**.
5. Open **Plugins → Add plugin → Upload plugin**, select `wp-content/plugins/lgsdn-core.zip`, and choose **Replace current with uploaded**.
6. Clear any hosting/page cache and check the homepage, Playbook listing, and case studies on desktop and mobile.

These updates replace code only. Local images, article edits, and per-case-study settings (including the header window checkbox) live in the database and must be set or transferred separately on the live site. Do not repeat the starter-content import for routine updates.

## Design system

The theme includes a responsive specimen template for reviewing typography, colour, spacing, layout, interface elements, and contour artwork. In the local development database it is available at <http://localhost:8080/design-system/>.

See [`DESIGN-SYSTEM.md`](DESIGN-SYSTEM.md) for the editable token locations and breakpoint rationale. The type and spacing scales live in `wp-content/themes/lgsdn/theme.json`; responsive component rules live in `wp-content/themes/lgsdn/style.css`.

Interactive [contour experiments](http://localhost:8080/wp-content/themes/lgsdn/prototypes/contours/) compare gentle waves and Perlin motion using SVG or Canvas, with adjustable movement and display settings. They are standalone prototypes and do not change the homepage. Implementation notes and geometry checks are in `wp-content/themes/lgsdn/prototypes/contours/README.md`.

## Content model

- **Case studies**: structured examples classified by Service, Practice, Council, Purpose, and Challenge.
- **People**: name, biography, image, role, organisation, and optional profile link.
- **Events**: description, date range, location, optional map link, format, and an optional booking link with configurable call to action text.
- **Pages**: Home, Join, Network, Events, Playbook, and ordinary information pages.

Service, Practice, Purpose, and Challenge are controlled vocabularies: editors can assign terms but only administrators can change the vocabulary. Editors may add and manage Council terms. Council terms are shared by People and Playbook items; each can have an optional logo, and the Network page lists only councils assigned to published Playbook items.

Each Playbook item has a public **Case study author** selected from People and may have several Practice terms. Editors select one assigned term as the **Primary practice**; that term controls the card colour and fallback contour. Practice, Service, Challenge, Case study author, and Primary practice must be complete before an item can be published through the block editor.

If the block editor reports that it could not update a Playbook metadata value, an administrator can open **Tools → Repair case study metadata**. The repair keeps the value WordPress currently reads and removes duplicate rows from single-value fields registered for Playbook items, including WordPress core fields such as footnotes.

Administrators manage the approved colour for each term under **Case studies → Practices**. The colour control is intentionally limited to the design-system palette rather than accepting arbitrary values. Each colour’s fallback contour is a theme asset named `practice-contour-{colour}.svg`; WordPress displays an administrator notice if a palette colour does not have a matching file.

## Editing service area pages

Open **Case studies → Service areas**, then edit a service area. The **Introduction** rich-text field controls the short article-style text above its case studies. It supports paragraphs, headings, links, and lists. If it is blank, the page uses the existing **Description**; if both are blank, the introduction is omitted. For a new service area, create it first and then open its edit screen to add an introduction.

The page uses the service area's name, colour, and icon, followed by shared Playbook cards for all published items assigned to that area, including secondary assignments. Items appear newest first, without pagination. Service area editing retains the existing administrator permissions.

## Editing the homepage

Edit **Pages → Home** and use the **Homepage content** panel for the lead copy and the three feature cards. Each card has an editable title, body, and destination page. The featured Playbook item remains static in the homepage renderer for now.

Homepage events come from **Events**. The **Event details** panel provides start and end dates, location, an optional map link, format, an optional booking link, optional call to action text, and a repeatable Resources control. Resource rows accept an external URL or a file selected or uploaded through the WordPress Media Library. A map link appears beneath the location on the event page. Complete resource label-and-link pairs appear directly below the registration action, separated by a fine divider, with the event description following. Resources stay with the event details above the photo in single-column layouts. The homepage Join/event spotlight includes events in the next seven days and keeps today's events visible as Today until midnight in the WordPress site timezone, even after their start time. When call to action text is blank, the event page uses **Register**. The homepage selects the next five valid upcoming events, displays them in descending date order, and appends the most recent past event. Month/year labels and weekdays come from the start date in the WordPress site timezone. An event becomes past when its start time passes. Missing or invalid start dates are omitted.

Use the standard WordPress content editor on an event to describe what it is. This article body appears on the event's detail page and supports the usual blocks, including paragraphs, headings, links, lists, and images.

Edit **Pages → Join us at an event** to change the title and introductory content above the complete events timeline. The dedicated Events page template keeps the generated listing in place beneath that editable content. Individual events continue to be managed under **Events** and use URLs below `/events/`.

The Events page at `/events/` reuses the timeline for every valid upcoming and past event. Its rows have increased vertical spacing and larger photos while the homepage keeps the compact curated variant.

Every timeline action is **Details** and links to the event page; titles link there too. Upcoming event pages with a booking link display the configured call to action with an external-link icon. Past events hide that booking action and have a **Past** badge and a dashed timeline rail. The shared timeline retains its month gutter on small screens. At the compact breakpoint, every event keeps its action below the title; pictured events place both rows beneath the image. From the medium breakpoint, pictured events keep their content and action together beneath the image.

Add an event photo using **Featured image** in the event editor. Below the wide breakpoint, the timeline shows a rounded, full-width photo above the event content and action. On wide layouts, the photo sits beside the content at up to 128px wide on the homepage and 192px wide on the Events page. When an event has a format, the photo carries a black **In person**, **Online**, or **Hybrid** tag at its top-left edge. Past-event photos are faded slightly while their format tags remain fully opaque. Events without a featured image use the available space for their text, with no placeholder or format tag; for these events, the format remains the fallback location text.

## Editing the network page

Manage members under **People**. Add the person's name as the title, their full biography in the main editor, their role in **Person details**, their council in **Councils**, and their portrait using **Featured image**. The optional excerpt is used as the short biography on the Network page; when it is blank, the page creates a short version from the main biography.

The Network page lists every published person in menu order and then alphabetically. Portraits are circular with the shared ink stroke. Profiles without an uploaded portrait use `assets/images/person-fallback.png`. People do not have separate public detail pages. A profile with published case studies has a link to those Playbook items.

## Editing the contribution form

The Contribute page embeds a Forminator form. Edit its questions under **Forminator → Forms → Expression of interest**; the page copy and the sample conversation email are editable under **Pages → Contribute**. The form sends its notification to users with the `manage_options` capability and has **Store submissions in database** disabled. Keep the notification recipients and storage setting in place when revising the form. The visible `contribute@example.org` address is a placeholder to replace once the contact address is agreed.

Forminator and its form configuration are stored in WordPress, not in the theme or LGSDN Core export archives. Install and activate Forminator in each WordPress environment, create or import the form, set its notification recipients and disable database storage, then update the form ID in the Contribute page embed if it differs from that environment's ID.

## Website feedback, credits and analytics

The shared feedback strip appears above the navigation, including the separately rendered homepage. Footer navigation links to `/feedback/`, `/about-this-website/` and `/privacy/`. LGSDN Core creates these editable pages on the next WordPress admin request without replacing existing page content. Their initial copy lives in the theme's matching `patterns/*-content.php` files.

For the first deployment, upload the updated theme and plugin ZIPs and open WordPress admin. The plugin copies the bundled starter content into any missing Website feedback, About this website and Privacy and analytics pages. The starter files were verified against the complete local page content, including credits and typography links. No separate page-content import is needed. After creation, edit the pages normally in WordPress; those database edits are independent of the starter files and will not be overwritten by later theme/plugin updates. Pages already present on hosting are preserved, not refreshed from the starters. There is no bespoke page-transfer tool or Tools menu entry.

The feedback page uses `[lgsdn_feedback_form]`. When Forminator is active, LGSDN Core creates the **Website feedback** form and saves its environment-specific ID in `lgsdn_feedback_form_id`. Email is optional, the main feedback field is required, a honeypot is enabled, and database submission storage is disabled. Notifications initially go to the WordPress administrator email. Review the recipient, mailbox retention and outbound email delivery before launch. The form's success message does not prove an email has reached its destination.

Use the free **Independent Analytics** plugin (`independent-analytics`) for page readership, referrals and geographic reports. Hosted Plausible was ruled out because analytics must have no subscription cost; Koko's country reports require its paid add-on. Install and activate Independent Analytics separately in each environment; third-party plugins are not included in our export ZIPs. On the next admin request, LGSDN Core configures daily visitor salt rotation, excludes logged-in visitors and selects the local geolocation database. This happens once per environment and preserves later administrator changes. Verify these settings after updates, confirm the geolocation database is available, and keep WordPress cron working so salt rotation runs.

The privacy page includes `[lgsdn_analytics_choice]`. Visitors can exclude their browser with a one-year preference cookie. LGSDN Core blocks Independent Analytics collection requests carrying that cookie or DNT/GPC headers at the REST endpoint, including requests from cached pages. No analytics consent banner has been added. Reassess notice/consent requirements if collection changes; cookie-free collection is not a blanket legal exemption. The free plugin also records approximate city and device data, so the privacy text does not describe it as country-only.

Before launch in the hosted WordPress installation:

- Deactivate **Koko Analytics** (and its Pro add-on if present), enable only Independent Analytics, and clear hosting/page caches. Koko was absent from the local installation; it has not been removed from hosting.
- Verify ordinary visits create analytics records and that excluded visits do not. Localhost cannot validate real visitor geography.
- Complete the privacy notice with the responsible organisation/contact, legal basis, hosting details, analytics and mailbox retention periods, and applicable rights information. The current copy is a pre-launch starting point.
- Complete the grant acknowledgement/reference and icon-by-icon Noun Project credits when supplied. Confirm the personal credit and AI-use statement.
- The supplied AHRC colour PNG is installed at `assets/images/partners/ahrc.png` on a small rounded white backing for contrast against the black footer. The UAL SVG was supplied by Gareth and is installed at `ual-white.svg`. The footer uses organisation-name links until artwork exists. The Camden white SVG is the user-supplied official asset from https://www.wemakecamden.org.uk/wp-content/uploads/2022/05/Camden-logo-white-01.svg, saved locally as `camden-white-01.svg`. Partner presentation uses shared footer CSS and the existing small-screen breakpoint; update both footer prototypes when artwork is added.
- The GitHub link is on **About this website**. Repository history, secrets and licences still need a dedicated review before launch; visibility has not been changed.

Run the focused integration checks after setup:

```sh
docker compose run --rm cli eval-file /var/www/html/wp-content/plugins/lgsdn-core/tests/website-feedback.php
```

## Planned work

- Show the selected author or contributor on Playbook case-study cards.

## Library naming

Public navigation uses Library and the listing title is Library of Practice. Individual items are Case studies in WordPress and Case study in analytics page-type labels. The existing `lgsdn_playbook` post type, block names, metadata keys and template names are deliberately retained. Public URLs now use `/library/` for the listing and `/library/{case-study-slug}/` for individual case studies. A one-time admin migration updates editorial references in pages, case studies, people and forthcoming events, preserving markup attributes and URLs. Historical event titles and copy retain their original names. A separate one-time URL migration runs on the next admin request after deployment: it moves the listing page in place, updates stored internal links and LGSDN link metadata, and refreshes rewrite rules. Old `/playbook/` paths redirect permanently to the matching `/library/` paths with their query strings preserved. If both listing slugs already exist, the migration pauses with an admin notice instead of merging pages. Internal links in source files point directly to Library URLs. Any saved analytics filter matching `/playbook/` explicitly must be changed to `/library/`; post-type filters still use the same `lgsdn_playbook` identifier.
