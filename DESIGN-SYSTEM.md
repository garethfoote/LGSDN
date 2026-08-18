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

The six controlled Service area colours mirror the `Brand Colors` collection in the Figma `Sketching` file. The admin selects the Base-mode token by name; it never stores a one-off hexadecimal value. The Contrast-mode values are recorded beside the Base values in `LGSDN_Service_Styles` for components that need the alternate Figma mode.

## Breakpoints

CityLAB Berlin uses several overlapping thresholds around 480, 768, 1024, 1280, and 1440px. LGSDN deliberately starts with fewer layout modes:

- **Compact:** below `48rem` / 768px
- **Medium:** `48rem` / 768px and above
- **Wide:** `64rem` / 1024px and above
- **Maximum content width:** `85rem` / 1360px

Use a breakpoint only when the content needs it, rather than targeting particular devices.

## Service area colours

Service-area cards take their visual identity from their primary Service area. Administrators assign one of six controlled tokens to each Service area term; arbitrary colours are not accepted.

| Token | Base | Contrast | Card text |
|---|---|---|---|
| Service area gold | `#FAC558` | `#AB9300` | Ink |
| Service area blue | `#4B66FF` | `#4B5AFF` | White |
| Service area purple | `#C6AFE3` | `#9F8AC0` | Ink |
| Service area olive | `#4B5A2B` | `#4B5A37` | White |
| Service area orange | `#FF9D4D` | `#EA7200` | Ink |
| Service area pink | `#FACDE1` | `#B3889B` | Ink |

The palette values are owned by `LGSDN_Service_Styles`. Service-area term metadata stores only the stable colour token name rather than a hexadecimal value or asset URL. The legacy `lilac` token is read as `purple` so existing content keeps its appearance. Practice terms no longer expose this palette in their admin screen; their existing token metadata remains available to preserve legacy practice contour fallbacks.

Playbook taxonomy tags have two roles:

- **Practice tag:** white with the existing Muted border and text token.
- **Secondary taxonomy tag:** Light grey (`#BDBDBD`) with Ink text. This reusable role covers Service, Purpose, Challenge, Council, and other non-primary classifications.

Card typography uses the dedicated 20px Card title role at Medium weight, plus Body small for the author badge, taxonomy labels, and date.

## Type scale

The body face is **Atkinson Hyperlegible Next**. Titles and headings use **Srbija Sans** at its regular weight. The font stacks and roles live in `settings.typography.fontFamilies` and `styles.elements.heading` in `theme.json`.

The family names are wired into the theme now. Atkinson Hyperlegible Next is limited to Regular (400) and Medium (500); the site does not synthesize or use Light or Bold. Srbija Sans uses Regular for headings. Before deployment, add any licensed WOFF2 files to `assets/fonts/` and add their `fontFace` entries to the matching family in `theme.json`.

Atkinson Hyperlegible Next is available under the SIL Open Font License. Srbija Sans permits website embedding but restricts redistributing its font files, so obtain it from the official Srbija Sans download and do not place it in a public repository without confirming that distribution is permitted.

The scale follows the semantic roles visible in Figma. Change the `settings.typography.fontSizes` array in `theme.json` to tune it.

| Token | Typeface | Size / line height |
|---|---|---|
| Display | Srbija Sans | 40px / 110% |
| Page title | Srbija Sans | 36px / 115% |
| Section title / H2 | Srbija Sans | 36px / 120% |
| Page lead | Atkinson Hyperlegible Next | 24px compact, 28px medium, 32px wide / 150% |
| Article lead | Atkinson Hyperlegible Next | 24px compact, 28px medium and wide / 150% |
| Event title | Atkinson Hyperlegible Next | 22px / 125% |
| Feature body | Atkinson Hyperlegible Next | 18px / 150% |
| Card title | Atkinson Hyperlegible Next Medium | 20px / 150% |
| Body extra large | Atkinson Hyperlegible Next | 24px / 120% |
| Article body | Atkinson Hyperlegible Next | 18px compact, 20px medium and wide / 150% |
| Interface body | Atkinson Hyperlegible Next | 16px / 150% |
| Body small | Atkinson Hyperlegible Next | 14px / 145% |
| Navigation | Atkinson Hyperlegible Next | 30px compact, 20px medium and wide / normal |
| Tag | Atkinson Hyperlegible Next | 14px / 145% |

Page lead copy steps from 24px on compact screens, to 28px at the medium breakpoint, and 32px at the wide breakpoint. Article leads stop growing at 28px, while normal article paragraphs become 20px from the medium breakpoint onward. Navigation uses its own responsive token rather than the generic small-text preset. On the design-system page, JavaScript highlights the current breakpoint and reads the rendered sample’s computed font size and line height into that row, so CSS remains the source of truth.

## Spacing scale

Spacing tokens use a restrained progression: 4, 8, 16, 24, 32, 48, 72, and 96px. In WordPress they use the conventional slugs `10` through `80`, which avoids breaking existing block content. The 4px `10` token is reserved for tightly related component content such as a card title, taxonomy tags, and date.

Page gutters are fluid: `clamp(1.25rem, 4vw, 3.25rem)`.
