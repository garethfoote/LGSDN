# Theme fonts

The theme expects these font families:

- Atkinson Hyperlegible Next: body text, navigation and controls
- Srbija Sans: headings and display text

Use WOFF2 files and declare them in the matching `fontFamilies` entry in
`theme.json`. The family roles are already configured, so no component CSS needs
to change when the files are added.

Recommended initial files:

- `AtkinsonHyperlegibleNext-Regular.woff2` (400)
- `AtkinsonHyperlegibleNext-Medium.woff2` (500)
- `AtkinsonHyperlegibleNext-SemiBold.woff2` (600, Latin)
- `AtkinsonHyperlegibleNext-SemiBold-LatinExt.woff2` (600, extended Latin)
- `SrbijaSans-Regular.woff2` (400)

The public type system registers Atkinson Light, Regular, Medium and SemiBold.
Shared bold text (`strong` and `b`) uses SemiBold (600). The semibold subsets
come from Google Fonts and are served locally, with Unicode ranges registered
in `theme.json`.

Atkinson Hyperlegible Next is licensed under SIL OFL 1.1. Srbija Sans permits
web embedding but has restrictions on redistributing the font itself. Keep the
Srbija file out of a public repository unless its licence terms are satisfied.
