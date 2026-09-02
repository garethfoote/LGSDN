# LGSDN project rules

- Treat repeated UI patterns as shared components. Before changing one instance, audit every render path and breakpoint for matching content, typography, spacing, borders, and interaction states.
- Prefer shared BEM classes and design tokens for common presentation. Use scoped modifiers only for intentional variants.
- Service cards must keep the label `Service` and the shared title typography consistent across the homepage, Playbook listing, and article detail views at wide, intermediate, and small breakpoints.
- Use sentence case for interface copy, including button labels and headings, unless a proper noun requires capitalization.
