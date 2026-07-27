# 20 — The Custom "Review" Post Type (Testimonials, Not Product Reviews)

Resolves QA question: *"Not sure why they have custom review functionality
... currently 14 reviews in total."*

## Short answer: it's curated marketing testimonials, not WooCommerce product reviews

Two independent pieces of evidence confirm this:

1. **WooCommerce's own native review system (comments) is unused**:
   `SELECT COUNT(*) FROM wp_comments WHERE comment_type='review'` returns
   **zero**. No product has ever received a real customer review through
   WooCommerce's built-in mechanism.
2. **Every single `review` CPT post is 5 stars** (`stars` meta,
   `min_value=1`/`max_value=5`/`default=5`): all 14 rows are `stars = 5`,
   zero variance. Real organic customer reviews are essentially never
   100% five-star across 14 independent respondents — this is the
   signature of **hand-picked, marketing-curated testimonials** (collected
   from happy customers, likely via email/survey, then manually entered
   by a site admin), not a live, moderated public review feed.

## Data model (confirmed via `wp_jet_post_types` + direct post meta inspection)

```
Post title:  the reviewer's first name (e.g. "Leanne", "Richard", "Mark")
text:        the review/testimonial body (textarea, required)
stars:       1–5 rating, default/actual value always 5 (number, required)
```

Not tied to any specific product — there's no product relationship field
on this CPT (confirmed: its `meta_fields` only declares `text` and
`stars`). These are general "customers love Chairforce" testimonials, not
"this specific customer reviewed this specific chair."

## Where they're displayed (3 Listing Grid templates — file `14` §F)

- "Homepage - Review Card" — a testimonials section/carousel on the
  homepage.
- "Reviews - Review Card" — likely a dedicated testimonials/reviews page,
  text-focused card.
- "Reviews - Image Card" — same page or section, a variant using each
  post's featured image (if set) alongside the quote.

A saved Query Builder query named simply `"reviews"` (file `14` §G, ID 25,
`Posts Query`) feeds these listings — a plain "all published `review`
posts" query, nothing conditional.

## Why build it this way instead of using WooCommerce reviews?

Almost certainly a deliberate choice: WooCommerce's native review system
is tied to actual verified purchases/comments on individual products,
which (a) requires customers to actually leave reviews (slow to
accumulate, mixed ratings, requires moderation) and (b) only makes sense
*per product*, not as a general "why shop with us" trust-building section.
A hand-curated testimonials CPT sidesteps both problems — it's
editorial content the marketing team controls directly, decoupled from
any specific product page.

## Rebuild recommendation

- **Zero migration complexity** — 14 rows, 2 plain fields (`text`,
  `stars`), no relationships, no images required (only sometimes present
  via the standard featured-image field). A new CPT + 2 ACF fields
  (WYSIWYG or textarea for `text`, a number/star-rating field for `stars`)
  reading/writing the **same** meta keys is a trivial rebuild.
- **Don't feel obligated to keep the "always 5 stars" pattern** — that's
  just what's been entered so far, not a system constraint; the field
  itself already supports 1–5.
- **Decide, while rebuilding, whether this should stay hand-curated
  testimonials or transition to real WooCommerce product reviews** (now
  that a full rebuild is happening anyway) — this is a legitimate content
  strategy question worth raising with the site owner, not something the
  existing implementation answers for you. If keeping testimonials, this
  CPT's simplicity means there's no reason not to just rebuild it as-is.
- **Confirm exactly which pages render the 3 Listing Grid templates**
  above before rebuilding the front-end (homepage section vs. a dedicated
  testimonials page vs. both) — not fully pinned down in this pass.
