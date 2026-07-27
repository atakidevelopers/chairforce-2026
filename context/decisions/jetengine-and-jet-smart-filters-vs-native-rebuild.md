# Decision: Do NOT keep JetEngine / Jet Smart Filters as live dependencies

**Status:** Decided — stick with the original plan.
**Date:** 2026-07-25
**Context research:** `context/existing-functionality/` (all files, especially `11`, `14`, `19`)

## The question that was raised

After the full audit of the existing site (`context/existing-functionality/`),
a second thought came up: since JetEngine + Jet Smart Filters are already
fully configured on the live site, could keeping them active (instead of
retiring them per the original plan) save meaningful rebuild time? Two
variants were on the table:

1. **Light touch**: keep JetEngine + Jet Smart Filters just for the
   content types/filters documented in files `14`/`15`/`19` (showrooms,
   gallery, reviews, category slider, the shop/gallery filter widgets).
2. **All-in**: also scrap ACF Pro and/or WP Grid Builder in favor of using
   JetEngine as the site's general field/content/filtering system going
   forward.

## Decision

**Rejected both variants. Proceeding with the original plan**: ACF Pro for
the field/data layer, native JSX blocks + `WP_Query`/REST (optionally via
WP Grid Builder for faceted filtering) for rendering, on FSE
templates/patterns. No Elementor, no Jet\* plugins, no Woodmart — as
originally scoped.

## Why (the reasoning that led here, so it can be re-evaluated later without re-deriving it from scratch)

1. **The expensive part has to be rebuilt regardless.** JetEngine's data
   layer (CPT/taxonomy/field/relation registration) is the cheap half to
   rebuild — the live data volumes are tiny (7 showrooms, 34 gallery
   items, 14 reviews, 6 category-slider items; see file `14`). The
   expensive half is the rendering + filtering UI (Listing Grid, Jet Smart
   Filters) — and that's **also the Elementor-coupled half**
   (`elementor-widget-jet-listing-grid`, `elementor-widget-jet-smart-filters-checkboxes`
   are literally Elementor widgets). Keeping JetEngine active doesn't
   avoid rebuilding this for Gutenberg/FSE — it has to be rebuilt either
   way. So the actual time saved by keeping JetEngine is closer to "an
   afternoon of ACF field-group building per content type," not "the
   whole feature."
2. **Scrapping ACF Pro for JetEngine would fragment the field system.**
   This theme's conventions (`10-acf-integration.mdc`, the existing
   `acf-json/*.json` field groups, `class-acf.php`) already make ACF the
   one field-management system site-wide. Adding JetEngine just for new
   CPTs creates two parallel, inconsistent systems — worse than either
   alone.
3. **"Jet Filters instead of WP Grid Builder" is plugin-vs-plugin, not
   plugin-vs-custom-code.** Both are third-party, DB/GUI-configured
   systems. If the actual worry is DB-configured black boxes (see #4),
   swapping one for the other doesn't address it. Where they do differ
   technically: **WP Grid Builder has native, first-class Gutenberg block
   support** (filters the actual Query Loop / WooCommerce Product
   Collection blocks natively); Jet Smart Filters is Elementor-first with
   no confirmed Gutenberg-native equivalent. Given this theme is
   committed to FSE/Gutenberg, WP Grid Builder is the better-fitting tool
   of the two — independent of "is it already configured on the old
   site" (it isn't installed in the new theme yet either, so this is
   "which system to adopt," not "replace a working one").
4. **DB-configured config is a genuine ongoing maintenance/git cost, not
   just an initial-setup cost.** ACF field groups are JSON files in
   `acf-json/` — diffable in a PR, deployable by pushing code. JetEngine's
   CPTs/taxonomies/relations/queries live in custom DB tables
   (`wp_jet_post_types`, `wp_jet_rel_default`, etc.) with no first-class
   git story — staging/prod drift and rollback both get harder. This was
   explicitly flagged as a concern going into this decision and weighed
   heavily.
5. **Strategic consistency.** The whole point of the original
   Woodmart/Elementor/Jet\* retirement was to get out of a DB-configured,
   hard-to-audit, page-builder-coupled, vendor-dependent stack. Re-adopting
   any part of that stack as a live dependency — even partially —
   reopens the exact risk category the rebuild was meant to close, for a
   savings that's smaller than it first appears (see #1).

## What would change this decision (revisit triggers)

Worth re-opening this if, during actual implementation:

- The `/gallery/` page's faceted filtering + infinite scroll (file `15`)
  turns out to be dramatically more effort than expected using WP Grid
  Builder/custom JSX, **and** the business is willing to accept
  Elementor-adjacent tooling back into the stack to hit a deadline. (Even
  then: prefer scoping the feature down — e.g. simpler filtering, classic
  pagination — over reintroducing the plugin stack.)
- WP Grid Builder itself turns out to be a poor fit once actually
  evaluated hands-on (e.g. licensing cost, missing a specific facet type
  needed here) — in that case the alternative to consider first is
  **fully custom filtering**, not Jet Smart Filters, per point #3 above.
- A future WordPress/JetEngine direction changes the Elementor-coupling
  facts this decision rests on (e.g. JetEngine ships genuinely first-class
  Gutenberg Listing Grid + Filter blocks) — worth a quick fact-check
  before assuming this still applies.

## Related

- `context/existing-functionality/11-recommendation-and-implementation-plan.md` — the feature-by-feature rebuild plan this decision keeps in place.
- `context/existing-functionality/14-jet-content-types-and-showrooms.md`, `19-master-rebuild-registration-checklist.md` — the full inventory of what's being rebuilt instead of ported.
