# tour-miles

Ground-distance tour leaderboards from geo-tagged stops. Haversine math
from the 1700s, honest-skip ranking, one PHP file, zero dependencies.

Extracted from production: a public leaderboard ranking 99 touring DJs
by how far they travel in a year, where the methodology page exists
because journalists actually read it.

## Quick start

```php
require __DIR__ . '/src/tour-miles.php';

$r = tour_miles_rank(array(
    'DJ A' => array(
        array('date' => '2026-06-05', 'lat' => 25.7617, 'lng' => -80.1918, 'venue' => 'Bayfront Park', 'city' => 'Miami'),
        array('date' => '2026-06-06', 'lat' => 40.7580, 'lng' => -73.9855, 'venue' => 'Brooklyn Mirage', 'city' => 'New York'),
    ),
    // ...
));

foreach ($r['rows'] as $row) { /* name, total_mi, legs, bar (0-100) */ }
// $r['skipped'] = artists too under-stopped to measure, counted not dropped
```

A runnable example: `php examples/leaderboard.php`.

## The parts that matter

- **NaN guard** - `asin(min(1.0, sqrt($h)))`; floating-point jitter on
  near-identical points otherwise poisons a whole artist's total
- **Same-venue trap** - consecutive stops at the same venue+city are one
  gig across nights, not travel; skip the leg, not the night
- **Round once** - kilometers summed, converted, and rounded on the
  total; per-leg rounding compounds
- **Honest skips** - unmeasurable artists are counted and reported,
  never silently dropped

Full reasoning in [docs/methodology.md](docs/methodology.md).

## When not to use it

- Flight-path or road-distance routing (this is great-circle ground
  distance; a ferry-heavy tour is "wrong" by design and fine)
- Anything needing a real routing engine or per-leg transport modes

## License

MIT
