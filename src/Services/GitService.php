<?php

namespace MominAlZaraa\FilamentLocalization\Services;

use Illuminate\Support\Facades\Process;

class GitService
{
    public function isGitRepository(): bool
    {
        $result = Process::run('git rev-parse --is-inside-work-tree 2>/dev/null');

        return $result->successful();
    }

    public function isWorkingDirectoryClean(): bool
    {
        $result = Process::run('git status --porcelain');

        return $result->successful() && empty(trim($result->output()));
    }

    public function createCommit(string $message): void
    {
        // Stage all changes
        Process::run('git add .');

        // Create commit
        $result = Process::run("git commit -m \"{$message}\"");

        if (! $result->successful()) {
            throw new \Exception('Failed to create git commit: '.$result->errorOutput());
        }
    }

    public function getLastCommitHash(): string
    {
        $result = Process::run('git rev-parse HEAD');

        return trim($result->output());
    }
}
