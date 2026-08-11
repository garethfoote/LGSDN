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
- `SrbijaSans-Regular.woff2` (400)

The public type system uses only Atkinson Regular and Medium. The Light file
may remain in the repository for reference, but it is deliberately not
registered or used by the theme.

Atkinson Hyperlegible Next is licensed under SIL OFL 1.1. Srbija Sans permits
web embedding but has restrictions on redistributing the font itself. Keep the
Srbija file out of a public repository unless its licence terms are satisfied.
