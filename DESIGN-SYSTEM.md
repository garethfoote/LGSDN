# LGSDN design system

The browser is the source of truth for the LGSDN visual system. The WordPress editor receives the same tokens, so editors see a close representation of the published site.

## Where to edit things

| What | Source of truth |
|---|---|
| Type sizes, colours, content widths, spacing presets | `wp-content/themes/lgsdn/theme.json` |
| Font files and font-face declarations | `wp-content/themes/lgsdn/assets/fonts/` and `wp-content/themes/lgsdn/theme.json` |
| Breakpoints, component layout and responsive behaviour | `wp-content/themes/lgsdn/style.css` |
| CSS reset | `wp-content/themes/lgsdn/assets/css/reset.css` |
| Design-system specimen markup | `wp-content/themes/lgsdn/templates/page-design-system.html` |
| Placeholder contours | `wp-content/themes/lgsdn/assets/images/practice-contour-*.svg` |
| Static homepage and isolated component examples | `wp-content/themes/lgsdn/prototypes/` |
| Homepage and isolated component styles | `wp-content/themes/lgsdn/assets/css/homepage.css` |

## Colour palette

The controlled Service area colours are managed by `LGSDN_Service_Styles`. The admin selects a stable token name; it never stores a one-off hexadecimal value.

## Breakpoints

CityLAB Berlin uses several overlapping thresholds around 480, 768, 1024, 1280, and 1440px. LGSDN deliberately starts with fewer layout modes:

- **Compact:** below `48rem` / 768px
- **Medium:** `48rem` / 768px and above
- **Wide:** `64rem` / 1024px and above (laptop scale)
- **Extra large:** `90rem` / 1440px and above (full large-screen scale)
- **Maximum content width:** `85rem` / 1360px

Use a breakpoint only when the content needs it, rather than targeting particular devices.

## Service area colours

Service-area cards take their visual identity from their primary Service area. Administrators assign one of six controlled tokens to each Service area term; arbitrary colours are not accepted. The cards use white text and icons across the homepage, Playbook listing, service archive, and article detail view.

| Service colour | Gradient top | Gradient bottom | White text contrast at bottom |
|---|---|---|---|
| Mustard | `#B59B00` (0–45%) | `#897600` (100%) | 4.51:1 |
| Blue | `#5A6CFF` | `#4B5AFF` | 4.99:1 |
| Purple | `#A183C2` | `#876AA9` | 4.50:1 |
| Green | `#4C5B37` | `#3A472B` | 9.92:1 |
| Orange | `#F66F00` | `#BD5B00` | 4.51:1 |
| Pink | `#D081A4` | `#9D667E` | 4.53:1 |

The palette and card gradient angle are owned by `LGSDN_Service_Styles`. Gradients run vertically from the decorative top stop to the darker bottom stop, behind the white service title. Mustard, pink, orange, and purple hold their lighter colour through 45% of the card before transitioning to their darker bottom stop. The bottom stop meets WCAG AA's 4.5:1 contrast requirement for normal white text. Service-area term metadata stores only the stable colour token name. The legacy `lilac` token is read as `purple`; older installations that stored `pink` continue to use the restored pink token. Practice terms retain the original palette values for their legacy contour fallbacks.

Small service-area taxonomy tags use Ink on mustard, purple, orange, and pink backgrounds, where white fails the 4.5:1 normal-text threshold. Blue and green retain white tag text because those pairings pass.

Taxonomy tags use Atkinson Hyperlegible Next Regular (400) across homepage case-study cards and Playbook case-study cards, including both service-area and other classification tags.

Playbook taxonomy tags have two roles:

- **Practice tag:** white with the existing Muted border and text token.
- **Secondary taxonomy tag:** Light grey (`#BDBDBD`) with Ink text. This reusable role covers Service, Purpose, Challenge, Council, and other non-primary classifications.

Card typography uses the dedicated 20px Card title role at Medium weight, plus Body small for the author badge, taxonomy labels, and date.

## Type scale

The body face is **Atkinson Hyperlegible Next**. Titles and headings use **Srbija Sans** at its regular weight. The font stacks and roles live in `settings.typography.fontFamilies` and `styles.elements.heading` in `theme.json`.

The family names are wired into the theme now. Atkinson Hyperlegible Next uses Light (300), Regular (400), and Medium (500); the site does not synthesize other weights. Srbija Sans uses Regular for headings. Before deployment, add any licensed WOFF2 files to `assets/fonts/` and add their `fontFace` entries to the matching family in `theme.json`.

Atkinson Hyperlegible Next is available under the SIL Open Font License. Srbija Sans permits website embedding but restricts redistributing its font files, so obtain it from the official Srbija Sans download and do not place it in a public repository without confirming that distribution is permitted.

The scale follows the semantic roles visible in Figma. Change the `settings.typography.fontSizes` array in `theme.json` to tune it.

| Token | Typeface | Size / line height |
|---|---|---|
| Display | Srbija Sans | 40px compact, 48px laptop, 56px extra large / 110% |
| Page title | Srbija Sans | 36px / 115% |
| Section title / H2 | Srbija Sans | 36px / 120% |
| Page lead | Atkinson Hyperlegible Next | 24px compact, 28px medium and laptop, 32px extra large / 150% |
| Article lead | Atkinson Hyperlegible Next | 24px compact, 28px medium and wide / 150% |
| Event title | Srbija Sans | 28px at every breakpoint / 125% |
| Feature body | Atkinson Hyperlegible Next | 18px / 150% |
| Card title | Atkinson Hyperlegible Next Medium | 20px / 150% |
| Body extra large | Atkinson Hyperlegible Next | 24px / 120% |
| Article body | Atkinson Hyperlegible Next | 18px compact, 20px medium and wide / 150% |
| Interface body | Atkinson Hyperlegible Next | 16px / 150% |
| Body small | Atkinson Hyperlegible Next | 14px / 145% |
| Navigation | Atkinson Hyperlegible Next | 30px compact, 20px medium and wide / normal |
| Tag | Atkinson Hyperlegible Next | 14px / 145% |

Page lead copy steps from 24px on compact screens, to 28px at the medium and laptop breakpoints, and 32px at the extra-large breakpoint. Article leads stop growing at 28px, while normal article paragraphs become 20px from the medium breakpoint onward. Navigation uses its own responsive token rather than the generic small-text preset. On the design-system page, JavaScript highlights the current breakpoint and reads the rendered sample’s computed font size and line height into that row, so CSS remains the source of truth.

## Spacing scale

Spacing tokens use a restrained progression: 4, 8, 16, 24, 32, 48, 72, and 96px. In WordPress they use the conventional slugs `10` through `80`, which avoids breaking existing block content. The 4px `10` token is reserved for tightly related component content such as a card title, taxonomy tags, and date.

Page gutters are fluid: `clamp(1.25rem, 4vw, 3.25rem)`.

## Events timeline

The `lgsdn/events-list` block and both event prototypes share the `lgsdn-events__*` classes in `style.css`. Homepage section spacing remains in `homepage.css`. The timeline uses the existing 48rem and 64rem breakpoints: compact month gutter below 48rem, larger type and spacing from 48rem, and right-aligned actions from 64rem. A per-row decorative rail changes from solid to dashed at the first past event, including within a shared month. Month labels group by both year and month.

The Events page passes the block's `showAll` attribute, which includes the complete past history and applies the `lgsdn-events--all` modifier. That modifier increases row padding from the existing spacing scale and raises the image size to 9rem compact / 12rem medium and wide without changing the shared markup.

Event titles use the existing 28px base H3 size through `--lgsdn-heading-h3-base-size`; the older `large` font-size preset remains unchanged for other components. Past rows use muted text and put the Past badge before the date. Locations use uppercase presentation and a decorative map pin. The isolated Events specimen uses a fixed 20 October 2026 reference date to show the upcoming/past transition independently of the current date.

Run the deterministic date-selection and rendering checks with `docker compose exec -T wordpress php /var/www/html/wp-content/plugins/lgsdn-core/tests/events-timeline.php`. These checks do not modify the database.

Event photos use the native WordPress featured image at medium resolution. Below the wide breakpoint, a pictured event places its photo across the top in a dedicated row. At the compact breakpoint, the event content and action stack in separate rows beneath it; from the medium breakpoint, they share the row beneath the image. Homepage photos are 8rem high in this layout; the full-list modifier used on the Events page increases them to 9rem compact and 12rem from the medium breakpoint. From the wide breakpoint, photos return to an 8rem-wide column on the homepage or a 12rem-wide Events page column, with a minimum 1.25:1 landscape height that can stretch with the row. The image is cropped with `object-fit: cover`; past-event images use reduced opacity. A pictured event with a valid format displays a shared black format tag that protrudes from the image's top-left edge, while events without an image omit both the image row and tag.

WordPress image blocks have an optional **Window** style for interface screenshots. It adds decorative window chrome and a shadow around the image without changing the uploaded file or its alternative text. Playbook card images can use the same frame through the **Show the card image in a window frame** option; the image sits at a contained size on the existing mist background across Playbook listings, homepage case-study cards, related cards, and the featured Playbook presentation. Card-frame corner radius, title bar, and dots scale with the media width, while the article-body style keeps its larger treatment.

Custom LGSDN actions use `.button` with `.lgsdn-button--arrow` for internal onward journeys or `.lgsdn-button--external` for off-site links. The external-link variant uses `↗` to indicate a different site or domain; it does not imply that the link opens a new tab. Text links do not animate the icon, while button-style external actions retain the short diagonal movement. Their shared sizing and interaction rules live in `assets/css/buttons.css`, loaded on the frontend, in the editor, and by button specimens. Native WordPress blocks retain their generated `.wp-block-button__link` markup. Event timeline buttons inherit the shared padding and responsive type size, always say **Details**, and lead to the event page. An upcoming event page with a booking link uses the external-link variant and its editor-configured call to action text, defaulting to **Register** when that text is blank. Event and Playbook resources use the same link presentation whether the destination is a Media Library file or an external service, with `↗` retained as a subtle off-site cue. Resource links use the browser's normal navigation behavior and do not force a new tab. The subordinate **View on Google Maps** location link intentionally omits the icon to preserve its relationship with the location and avoid competing with the primary registration action.
