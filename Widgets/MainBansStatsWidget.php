<?php

namespace Flute\Modules\BansComms\Widgets;

use Flute\Core\Widgets\AbstractWidget;
use Flute\Modules\BansComms\Services\BansCommsService;
use Nette\Utils\Html;

class MainBansStatsWidget extends AbstractWidget
{
    public function __construct()
    {
        $this->setAssets([
            mm('BansComms', 'Resources/assets/js/widgets/main.js'),
            mm('BansComms', 'Resources/assets/scss/widgets/main.scss'),
        ]);

        $this->setReloadTime(30000);
    }

    public function render(array $data = []): string
    {
        return render(mm('BansComms', "Resources/views/widgets/main_stats"), [
            'stats' => $this->getMainStats()
        ]);
    }

    public function placeholder(array $settingValues = []): string
    {
        $row = Html::el('div');
        $row->addClass('row gx-3 gy-3');

        $col = Html::el('div');

        $placeHolder = Html::el('div');
        $placeHolder->addClass('skeleton');

        $col->addClass('col');
        $row->addHtml($col);
        $row->addHtml($col);
        $row->addHtml($col);
        $placeHolder->style('min-height', '120px')->style('min-width', '300px');

        $col->addHtml($placeHolder);

        $row->addHtml($col);

        return $row->toHtml();
    }

    public function getName(): string
    {
        return 'Main BansComms stats';
    }

    public function isLazyLoad(): bool
    {
        return true;
    }

    protected function getMainStats() : array
    {
        return app(BansCommsService::class)->getCountsForAllServers();
    }
}