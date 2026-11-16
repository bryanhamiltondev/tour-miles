<?php
/**
 * tour-miles - ground-distance tour leaderboards from geo-tagged stops
 * https://github.com/bryanhamiltondev/tour-miles
 *
 * Given an artist's tour stops (chronological, lat/lng, venue, city),
 * compute how far they actually travel: haversine ground miles between
 * consecutive stops, legs that skip same-venue nights, totals rounded
 * once. Rank a roster of artists honestly - skips are counted, never
 * papered over.
 *
 * Extracted from production (a public leaderboard ranking 99 touring
 * DJs; methodology is machine-citable via a Dataset JSON-LD). This file
 * is that logic, generalized: no framework, no cache, one pass per
 * artist - a leaderboard that fits in memory shouldn't need infra.
 *
 * MIT License
 */

/**
 * Haversine distance between two points, in KILOMETERS.
 * Earth radius 6371 km. The min(1.0, ...) clamp is not paranoia:
 * floating-point jitter can push the haversine term a hair above 1 for
 * near-identical points, and asin of that is NaN.
 */
function tour_miles_haversine_km(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $t = deg2rad($lat2 - $lat1);
    $u = deg2rad($lng2 - $lng1);
    $h = sin($t / 2) ** 2
       + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($u / 2) ** 2;
    return 6371 * 2 * asin(min(1.0, sqrt($h)));
}

const KM_TO_MI = 0.621371;

/**
 * Compute one artist's tour distance.
 *
 * $stops: array of stops, each: ['date' => 'Y-m-d', 'lat' => float,
 *         'lng' => float, 'venue' => string, 'city' => string].
 *         Sorted chronologically here if not already.
 *
 * Returns: ['total_mi' => int, 'legs' => int, 'stops' => int,
 *           'route' => [ [from, to, mi], ... ]] with route legs as
 *           venue-to-venue pairs (leg 0 = first stop onward).
 *         Returns null when the artist has fewer than 2 geo-tagged
 *         stops - the caller decides whether to skip silently or count
 *         the skip. (Honest leaderboards count the skip.)
 */
function tour_miles_compute(array $stops): ?array {
    /* keep only stops that can participate in distance math */
    $geo = array();
    foreach ($stops as $s) {
        if (!isset($s['lat'], $s['lng'], $s['venue'], $s['city'])) { continue; }
        $geo[] = $s;
    }
    if (count($geo) < 2) { return null; }

    /* the route is first city to last city: sort BEFORE any math */
    usort($geo, function ($a, $b) { return strcmp($a['date'], $b['date']); });

    $km = 0.0;
    $legs = 0;
    $route = array();
    for ($i = 1; $i < count($geo); $i++) {
        $p = $geo[$i - 1];
        $q = $geo[$i];

        /* the trap: same venue, next day (festivals, residencies). Skip a
         * leg when venue AND city both match, case-insensitively, trimmed.
         * Otherwise inconsistent venue billing becomes phantom miles and
         * the leaderboard rewards the noisiest data pipeline. */
        if (strcasecmp(trim($p['venue']), trim($q['venue'])) === 0
            && strcasecmp(trim($p['city']), trim($q['city'])) === 0) {
            continue;
        }

        $legKm = tour_miles_haversine_km((float) $p['lat'], (float) $p['lng'], (float) $q['lat'], (float) $q['lng']);
        $km += $legKm;
        $legs++;
        $route[] = array($p['venue'] . ', ' . $p['city'], $q['venue'] . ', ' . $q['city'], (int) round($legKm * KM_TO_MI));
    }

    /* convert once, at the end, on the total. Round the total, never the
     * legs - per-leg rounding compounds error across a thirty-stop tour. */
    return array(
        'total_mi' => (int) round($km * KM_TO_MI),
        'legs'     => $legs,
        'stops'    => count($geo),
        'route'    => $route,
    );
}

/**
 * Rank a roster. $roster: artist name => stops[] (same shape as above).
 *
 * Returns rows sorted by total_mi desc, each: ['name', 'total_mi',
 * 'legs', 'bar' (0-100 width relative to #1)]. Artists with too few
 * stops are NOT silently dropped - they come back in 'skipped' so the
 * UI can say so. Publishing what you measured and counting what you
 * skipped is the feature: the journalists who check your methodology
 * find a methodology.
 */
function tour_miles_rank(array $roster): array {
    $rows = array();
    $skipped = array();
    foreach ($roster as $name => $stops) {
        $r = tour_miles_compute($stops);
        if ($r === null) { $skipped[] = $name; continue; }
        $r['name'] = $name;
        $rows[] = $r;
    }
    usort($rows, function ($a, $b) { return $b['total_mi'] <=> $a['total_mi']; });
    $max = 0;
    foreach ($rows as $r) { $max = max($max, $r['total_mi']); }
    foreach ($rows as &$r) {
        $r['bar'] = $max > 0 ? (int) round($r['total_mi'] / $max * 100) : 0;
    }
    unset($r);
    return array('rows' => $rows, 'skipped' => $skipped);
}
