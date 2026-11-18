# Working tree review — 18 November 2026

This is a focused review of the accumulated changes, not a full security or visual accessibility audit. The initial review left everything unstaged and uncommitted; the subsequent commit batches are recorded below. At the start there were 56 modified tracked files and 33 untracked individual files; directory entries in `git status` hide some of those new files. The review also changed the existing timeline test and added this note.

## Findings fixed

- **Library URL migration:** the old matcher changed external scheme-relative links such as `//example.org/playbook/example/`, non-web links, and nested paths such as `/other/playbook/example/`. It also rewrote query values in root-relative URLs. The migration now matches complete URLs, checks the host and scheme, and replaces only the intended path prefix. Regression checks cover these cases. The migration completion flag was not reset; this fix does not rerun migrations against existing content.
- **Timeline test drift:** the standalone test lacked the `home_url()` stub needed by the current renderer. A second assertion still expected one past event, although the renderer intentionally fills sparse home timelines with up to three recent events. Both are corrected; all 29 checks pass. Production event selection was unchanged by this repair.
- **Accessible copy:** corrected the article taxonomy links' remaining “Filter playbook by …” label to “Filter library by …”.

## Cleanup worth doing separately

- **Export tracking resolved:** both ZIPs were removed from Git tracking with `git rm --cached`, with user approval. The local upload files remain available and are ignored. Rebuild them before deployment; the existing archives predate the final source changes.
- **Repeated palette selection:** `LGSDN_Service_Styles::for_term()` repeats palette selection already performed by `colours()`. It can use the resolved palette directly. `render_palette_switcher()` has no callers in the current source. The saved palette setting and URL preview still work; keep or remove those deliberately. The separate Practice colour table is intentional: Practice tags retain their own colours when Service colours change.
- **Export scripts differ:** `export-wordpress.py` increments versions; `export-wordpress.ps1` only builds archives. README prescribes the Python script. Consolidate or align the scripts before treating them as interchangeable.
- **Prototype and experiment material:** review `experiments/` and changed prototypes as reference material. Do not delete legacy feature-row CSS wholesale: the current homepage still uses those rows. Avoid a broad formatting pass while sorting the functional work.

## Suggested commit sequence

1. **Rename Playbook to Library and migrate public URLs.** Include both migration classes and their tests, post-type rewrite and label changes, internal links, and relevant bootstrap/asset-enqueue hunks. Keep stored post-type and metadata identifiers unchanged.
2. **Add website information, feedback and analytics preferences.** Include `class-website-pages.php`, its test, the three starter patterns, feedback strip, preference script, footer partners/artwork, and corresponding bootstrap, footer and theme CSS hunks.
3. **Unify Service cards and shared presentation.** Include Service/Practice palette work, card render paths, typography tokens/fonts, buttons, resources styling and relevant design-system examples. Keep shared CSS and matching markup in the same commit.
4. **Update the homepage Join/event spotlight and event details.** Include the spotlight renderer, carousel script, editor preview, regression tests, event resources/description arrangement, and homepage/timeline spacing. Include the associated CSS and enqueues.
5. **Document licensing and synchronise reference material.** Include licences, notices, credits, remaining README/design-system updates, and intentional prototype/experiment changes. Documentation that is needed to explain one of the earlier changes can accompany that commit instead.
6. **Prepare release artifacts.** Decide ZIP tracking first, then rebuild once from the verified source. The current ZIPs predate the last spacing adjustment and this migration repair.

Use partial staging (`git add -p`) for `style.css`, `homepage.css`, `functions.php`, the plugin bootstrap and mixed renderer files. Avoid splitting shared components into commits that leave their render paths inconsistent. If two groups cannot stand alone cleanly, combine them rather than inventing a misleading history. Check staged changes and run the affected checks before each commit.

## Commit batches completed

- `980a63f` — Stop tracking generated WordPress export archives.
- `06ee579` — Rename public Playbook content to Library and migrate URLs.
- `6e0b3e3` — Add website feedback, analytics preferences and partner footer.
- `60617cc` — Refine shared typography, service cards and resource presentation. Event resource markup is included with its shared CSS here to keep the component consistent.
- `8f71c56` — Add homepage event spotlight and refine responsive participation layout.
- The documentation batch contains this review, licences and notices, updated reference prototypes, README/design-system changes and the existing release version metadata.

Shared files were staged selectively without replacing working-tree files. PHP, JavaScript and JSON syntax checks were run against the staged content. The archived experiment patch preserves its original diff context whitespace and is excluded from whitespace checking; production source is checked normally. No commits were pushed and the local ZIPs were not rebuilt during batching.

## Verification completed

- PHP syntax: all 43 PHP files in the theme and plugin passed, including tests and new files; the repaired migration was linted again after editing.
- JavaScript syntax: all seven changed/new scripts passed `node --check`.
- JSON: all five changed JSON files parsed successfully.
- Six suites passed: standalone timeline (29 checks), Events admin list, homepage spotlight, Library URLs (including added regressions), Library naming, website feedback/analytics preferences.
- Local HTTP smoke checks: homepage, Library, Events, People, feedback, privacy, website information and the supplied event page returned 200 without PHP error output.
- `git diff --check` passed. `.env` is ignored and untracked.

The HTTP checks do not prove responsive appearance, email delivery, production geolocation or third-party plugin compatibility on hosting. Review the visible layouts before release; the local checks are not a substitute for that.
