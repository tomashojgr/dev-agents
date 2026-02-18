<?php

declare(strict_types=1);

namespace DevAgents;

use RuntimeException;

class TaskLoader
{
    private const TASKS_DIR = '.tasks';

    public static function load(string $taskId): array
    {
        $path = getcwd() . '/' . self::TASKS_DIR . '/' . $taskId . '/TASK.md';

        if (!file_exists($path)) {
            throw new RuntimeException("Task not found: $path");
        }

        $contents = file_get_contents($path);

        return self::parse($contents);
    }

    public static function listAll(): array
    {
        $dir = getcwd() . '/' . self::TASKS_DIR;

        if (!is_dir($dir)) {
            return [];
        }

        $tasks = [];
        foreach (glob($dir . '/*/TASK.md') as $file) {
            $taskId = basename(dirname($file));
            $tasks[$taskId] = self::parse(file_get_contents($file));
        }

        return $tasks;
    }

    private static function parse(string $contents): array
    {
        $task = [
            'status' => 'draft',
            'goal'   => '',
            'scope'  => [],
            'raw'    => $contents,
        ];

        // Parse status
        if (preg_match('/^status:\s*(.+)$/m', $contents, $m)) {
            $task['status'] = trim($m[1]);
        }

        // Parse goal
        if (preg_match('/^##\s*Goal\s*\n(.+?)(?=\n##|\z)/ms', $contents, $m)) {
            $task['goal'] = trim($m[1]);
        }

        // Parse scope (list items under ## Scope)
        if (preg_match('/^##\s*Scope\s*\n(.+?)(?=\n##|\z)/ms', $contents, $m)) {
            preg_match_all('/^[-*]\s*`?([^`\n]+)`?/m', $m[1], $items);
            $task['scope'] = $items[1] ?? [];
        }

        return $task;
    }
}
