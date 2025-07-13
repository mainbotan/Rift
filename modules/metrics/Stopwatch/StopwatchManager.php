<?php

namespace Rift\Metrics\Stopwatch;

use Symfony\Component\Stopwatch\Stopwatch;

class StopwatchManager {
    
    public function collectMetrics(Stopwatch $stopwatch, string $operationName): array {
        $events = $stopwatch->getSectionEvents('__root__');
        $metrics = [];
        $baseMemory = $events[$operationName]->getMemory();

        foreach ($events as $name => $event) {
            $metrics[$name] = [
                'duration_ms' => round($event->getDuration(), 2),
                'duration_human' => $this->formatDuration($event->getDuration()),
                'memory_bytes' => $event->getMemory(),
                'memory_diff_bytes' => $event->getMemory() - $baseMemory,
                'memory_human' => $this->formatBytes($event->getMemory()),
                'memory_diff_human' => $this->formatBytes($event->getMemory() - $baseMemory)
            ];
        }

        return [
            'timings' => $metrics,
            'summary' => [
                'total_time_ms' => $events[$operationName]->getDuration(),
                'peak_memory' => max(array_column($metrics, 'memory_bytes'))
            ]
        ];
    }

    private function formatDuration(float $ms): string {
        if ($ms < 1) {
            return round($ms * 1000, 2) . 'μs';
        }
        if ($ms < 1000) {
            return round($ms, 2) . 'ms';
        }
        return round($ms / 1000, 2) . 's';
    }

    private function formatBytes(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}