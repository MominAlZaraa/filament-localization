<?php

namespace MominAlZaraa\FilamentLocalization\Services;

use Illuminate\Support\Facades\Process;

class PintService
{
    public function isPintAvailable(): bool
    {
        $result = Process::run('which vendor/bin/pint');

        return $result->successful();
    }

    public function formatCode(): bool
    {
        if (!$this->isPintAvailable()) {
            return false;
        }

        $command = config('filament-localization.pint.command', 'vendor/bin/pint --dirty');
        
        $result = Process::run($command);

        return $result->successful();
    }

    public function formatCodeWithOutput(): array
    {
        if (!$this->isPintAvailable()) {
            return [
                'success' => false,
                'output' => 'Laravel Pint is not available',
                'error' => 'Pint executable not found'
            ];
        }

        $command = config('filament-localization.pint.command', 'vendor/bin/pint --dirty');
        
        $result = Process::run($command);

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput()
        ];
    }
}
