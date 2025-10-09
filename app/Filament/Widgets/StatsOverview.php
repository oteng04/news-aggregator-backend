<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Articles', Article::count())
                ->description('Total articles in database')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),

            Stat::make('Total Sources', Source::count())
                ->description('News sources configured')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('info'),

            Stat::make('Total Categories', Category::count())
                ->description('Article categories')
                ->descriptionIcon('heroicon-m-tag')
                ->color('warning'),

            Stat::make('Total Authors', Author::count())
                ->description('Article authors')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Articles Today', Article::whereDate('created_at', today())->count())
                ->description('Articles fetched today')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),

            Stat::make('Latest Article', Article::latest()->first()?->title ?? 'None')
                ->description('Most recent article title')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),
        ];
    }
}
