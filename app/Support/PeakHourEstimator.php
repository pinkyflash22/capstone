<?php

namespace App\Support;

class PeakHourEstimator
{
    private const BUCKET_SIZE = 3;

    /**
     * Estimate broad peak windows instead of claiming an exact hour from sparse data.
     * Each observation is ['hour' => 0..23, 'weight' => positive number].
     */
    public static function estimate(array $observations, int $minimumObservations = 5): array
    {
        $scores = array_fill(0, 24 / self::BUCKET_SIZE, 0.0);
        $counts = array_fill(0, 24 / self::BUCKET_SIZE, 0);
        $validObservations = 0;

        foreach ($observations as $observation) {
            $hour = filter_var($observation['hour'] ?? null, FILTER_VALIDATE_INT);
            $weight = (float) ($observation['weight'] ?? 0);
            if ($hour === false || $hour < 0 || $hour > 23 || $weight <= 0) {
                continue;
            }

            $bucket = intdiv($hour, self::BUCKET_SIZE);
            $scores[$bucket] += $weight;
            $counts[$bucket]++;
            $validObservations++;
        }

        if ($validObservations < $minimumObservations) {
            return self::unclear($validObservations, $scores, 'insufficient_data');
        }

        arsort($scores);
        $rankedBuckets = array_keys($scores);
        $topBucket = $rankedBuckets[0] ?? null;
        $secondBucket = $rankedBuckets[1] ?? null;
        $topScore = $topBucket !== null ? $scores[$topBucket] : 0.0;
        $secondScore = $secondBucket !== null ? $scores[$secondBucket] : 0.0;

        // Demand a meaningful lead. Exact or near ties must not be presented as a peak.
        if ($topScore <= 0 || ($secondScore > 0 && $topScore < ($secondScore * 1.15))) {
            return self::unclear($validObservations, $scores, 'tied_or_weak_signal');
        }

        return [
            'status' => 'clear',
            'label' => self::formatBucket($topBucket),
            'hours' => [self::formatBucket($topBucket)],
            'observation_count' => $validObservations,
            'confidence' => round(min(100, (($topScore - $secondScore) / max(1, $topScore)) * 100)),
            'reason' => null,
            'bucket_scores' => array_map(fn ($score) => round($score, 2), array_values($scores)),
        ];
    }

    private static function unclear(int $observationCount, array $scores, string $reason): array
    {
        return [
            'status' => 'unclear',
            'label' => 'No clear peak yet',
            'hours' => [],
            'observation_count' => $observationCount,
            'confidence' => 0,
            'reason' => $reason,
            'bucket_scores' => array_map(fn ($score) => round($score, 2), array_values($scores)),
        ];
    }

    private static function formatBucket(int $bucket): string
    {
        $start = $bucket * self::BUCKET_SIZE;
        $end = $start + self::BUCKET_SIZE - 1;

        return sprintf('%02d:00–%02d:59', $start, $end);
    }
}
