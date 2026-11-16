<?php
/* Minimal leaderboard: feed it stops, get ranked bars. Run: php leaderboard.php */
require __DIR__ . '/../src/tour-miles.php';

$roster = array(
    'DJ A' => array(
        array('date' => '2026-06-05', 'lat' => 25.7617, 'lng' => -80.1918, 'venue' => 'Bayfront Park', 'city' => 'Miami'),
        array('date' => '2026-06-06', 'lat' => 40.7580, 'lng' => -73.9855, 'venue' => 'Brooklyn Mirage', 'city' => 'New York'),
        array('date' => '2026-06-12', 'lat' => 51.5074, 'lng' => -0.1278, 'venue' => 'Printworks', 'city' => 'London'),
    ),
    'DJ B' => array(
        array('date' => '2026-06-05', 'lat' => 50.0880, 'lng' => 14.4200, 'venue' => 'O2 Arena', 'city' => 'Prague'),
        array('date' => '2026-06-06', 'lat' => 50.0880, 'lng' => 14.4200, 'venue' => 'O2 Arena', 'city' => 'Prague'), /* same venue next night: skipped */
        array('date' => '2026-06-07', 'lat' => 52.5200, 'lng' => 13.4050, 'venue' => 'Berghain', 'city' => 'Berlin'),
    ),
    'DJ C' => array( /* too few stops: lands in skipped, not dropped */
        array('date' => '2026-06-05', 'lat' => 41.9028, 'lng' => 12.4964, 'venue' => 'Villa Ada', 'city' => 'Rome'),
    ),
);

$r = tour_miles_rank($roster);

foreach ($r['rows'] as $row) {
    printf("%-6s %7s mi  %2d legs  |%s\n", $row['name'], number_format($row['total_mi']), $row['legs'], str_repeat('#', (int) ($row['bar'] / 4)));
}
if ($r['skipped']) {
    echo 'skipped (too few geo-tagged stops): ' . implode(', ', $r['skipped']) . "\n";
}
