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

## Editing the homepage

Edit **Pages → Home** and use the **Homepage content** panel for the lead copy and the three feature cards. Each card has an editable title, body, and destination page. The featured Playbook item remains static in the homepage renderer for now.

Homepage events come from **Events**. The **Event details** panel provides start and end dates, location, format, and an optional booking link. Upcoming events with a booking link display a **Book** button; past events display the **Past** label instead.
