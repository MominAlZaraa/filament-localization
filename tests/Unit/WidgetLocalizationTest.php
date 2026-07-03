<?php

use MominAlZaraa\FilamentLocalization\Analyzers\WidgetAnalyzer;
use MominAlZaraa\FilamentLocalization\Generators\TranslationFileGenerator;
use MominAlZaraa\FilamentLocalization\Generators\WidgetModifier;

function widgetPanel(string $id = 'admin'): object
{
    return new class($id)
    {
        public function __construct(private string $id) {}

        public function getId(): string
        {
            return $this->id;
        }
    };
}

test('widget analyzer detects stat titles descriptions and headings', function () {
    $analyzer = app(WidgetAnalyzer::class);
    $method = new ReflectionMethod(WidgetAnalyzer::class, 'analyzeStats');
    $method->setAccessible(true);

    $php = <<<'PHP'
class OrdersOverview extends StatsOverviewWidget
{
    protected static ?string $heading = 'Orders Overview';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Orders', $this->getTotal()),
            Stat::make('Revenue', $this->getRevenue())
                ->description('Last 30 days'),
        ];
    }
}
PHP;

    $stats = $method->invoke($analyzer, $php);
    $types = array_column($stats, 'type');

    expect($types)->toContain('widget_heading_property')
        ->and($types)->toContain('stat_title')
        ->and($types)->toContain('stat_description');

    $heading = collect($stats)->first(fn (array $s) => $s['type'] === 'widget_heading_property');
    expect($heading['value'])->toBe('Orders Overview')
        ->and($heading['translation_key'])->toBe('heading');
});

test('widget modifier localizes stats headings and chart labels', function () {
    $modifier = app(WidgetModifier::class);
    $panel = widgetPanel('admin');

    $analysis = [
        'widget_name' => 'OrdersOverview',
        'stats' => [
            [
                'type' => 'widget_heading_property',
                'value' => 'Orders Overview',
                'translation_key' => 'heading',
                'has_translation' => false,
            ],
            [
                'type' => 'stat_title',
                'value' => 'Total Orders',
                'translation_key' => 'total_orders',
                'has_translation' => false,
            ],
            [
                'type' => 'stat_description',
                'value' => 'Last 30 days',
                'translation_key' => 'last_30_days',
                'has_translation' => false,
            ],
            [
                'type' => 'widget_property',
                'value' => 'Monthly Trend',
                'translation_key' => 'monthly_trend',
                'has_translation' => false,
            ],
        ],
    ];

    $content = <<<'PHP'
class OrdersOverview extends StatsOverviewWidget
{
    protected static ?string $heading = 'Orders Overview';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Orders', 10)
                ->description('Last 30 days'),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => 'ignored',
                ],
            ],
        ];
    }

    public function configureChart(): static
    {
        return $this->heading('Monthly Trend');
    }
}
PHP;

    $modified = $modifier->modify($content, $analysis, $panel, false);

    expect($modified)
        ->toContain("return __('filament/admin/orders_overview.heading')")
        ->toContain("Stat::make(__('filament/admin/orders_overview.total_orders'), 10)")
        ->toContain("->description(__('filament/admin/orders_overview.last_30_days'))")
        ->toContain("->heading(__('filament/admin/orders_overview.monthly_trend'))");
});

test('translation file generator includes widget stat keys', function () {
    $generator = app(TranslationFileGenerator::class);
    $method = new ReflectionMethod(TranslationFileGenerator::class, 'buildWidgetTranslations');
    $method->setAccessible(true);

    $analysis = [
        'stats' => [
            [
                'type' => 'stat_title',
                'value' => 'Total Orders',
                'translation_key' => 'total_orders',
                'has_translation' => false,
            ],
            [
                'type' => 'widget_heading_property',
                'value' => 'Orders Overview',
                'translation_key' => 'heading',
                'has_translation' => false,
            ],
        ],
    ];

    $translations = $method->invoke($generator, $analysis);

    expect($translations)->toHaveKey('total_orders', 'Total Orders')
        ->and($translations)->toHaveKey('heading', 'Orders Overview');
});
