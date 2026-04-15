# Portal Recommendation Algorithm (v1)

## Goal
Provide a practical, explainable ranking for portal users using explicit preferences, behavior, and editorial priorities.

## Formula
For each published post candidate:

`final_score = positive_factors - penalties`

### Positive factors
- `favoriteCategory`: `matches * weight.favoriteCategory`
- `favoriteTags`: `matches * weight.favoriteTag`
- `favoriteAuthor`: `match ? weight.favoriteAuthor : 0`
- `interactionCategory`: `category_affinity_sum * weight.interactionCategory`
- `interactionTag`: `tag_affinity_sum * weight.interactionTag`
- `interactionAuthor`: `author_affinity * weight.interactionAuthor`
- `relatedSavedPosts`: `has_similarity ? weight.relatedSavedPost : 0`
- `recentBonus`: linear decay inside `recentHoursWindow`
- `trendingBonus`: logarithmic popularity normalization from `view_count` + `share_count`
- `editorialBonus`: featured/breaking flags + editorial priority bonus

### Penalties
- `seenPenalty`: `view_count_by_user * weight.seenPenalty`
- `diversityPenalty`: dynamic penalty when one category dominates top results

## Diversity and editorial guarantees
After scoring:
1. Apply category overexposure penalty (`themeOverloadPenalty`)
2. Re-sort candidates
3. Ensure at least `minEditorialGuarantee` items with editorial/trending signal in top N

## Where to tune
Config file: `app/Config/PortalRecommendation.php`

- `weights`: global scoring constants
- `interactionActionWeight`: behavior action multipliers
- `candidateLimit`: max scored candidates
- `cacheTtlSeconds`: recommendation cache expiry
- `minEditorialGuarantee`: editorial floor in top N
- `maxPerPrimaryCategory`: category diversity cap

## Cache strategy
- Computed scores are stored in `portal_user_recommendation_scores`
- Reused while not expired
- Refreshed on demand (`refresh=true`) or via optional cron:
  - `php spark portal:refresh-recommendations`

## Why this design
- Explainable for editors and product
- Lightweight for shared hosting
- Adjustable with config only
- No dependency on persistent workers or ML infrastructure
