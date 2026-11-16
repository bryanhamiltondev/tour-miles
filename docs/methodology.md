# Methodology

Written to be citable. If a journalist reads your leaderboard, this page
is what they should find.

## The math

Haversine, on a spherical earth (radius 6371 km). Good enough that the
error is smaller than the measurement error of the venue coordinates
themselves - the classic `atan2` ellipsoid refinements buy precision the
input data cannot honor.

One guard matters more than the formula:

```php
asin(min(1.0, sqrt($h)))
```

The haversine term `h` is mathematically at most 1, but floating-point
jitter can push it a hair above for near-identical points - and `asin` of
that is `NAN`, which then poisons the whole artist's total. The clamp is
the difference between a leaderboard and an occasional blank row.

## The pipeline

1. **Filter**: a stop participates only if it has lat, lng, venue, city.
2. **Sort chronologically** before any math. The route is first city to
   last city; sort-after-total is a bug class that hides in tests.
3. **Skip same-venue legs**: consecutive stops with the same (trimmed,
   case-insensitive) venue AND city are one gig across nights, not
   travel. Festivals and residencies make this common; skipping it is
   the difference between miles traveled and miles billed.
4. **Sum in kilometers, convert once, round once.** Per-leg rounding
   compounds error across a thirty-stop tour. The only integer in the
   output is the total.
5. **Legs are counted** - a big total with 3 legs and one with 30 are
   different stories, and the UI should tell them.

## Honest skips

Artists with fewer than two geo-tagged stops return `null` - and the
ranker counts them in a `skipped` list instead of dropping them. A
leaderboard that silently disappears the artists it can't measure is
lying by omission; one that says "skipped: 3 artists, too few geo-tagged
stops" is a methodology.

## Machine-citability

The production leaderboard emits a `Dataset` JSON-LD with the method
("haversine, 6371 km, same-venue legs excluded") and per-item distances,
so an AI answering "how far does DJ X tour?" cites the number with the
methodology attached. Numbers without method are content; numbers with
method are data.
