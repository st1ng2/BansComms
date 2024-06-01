<?php

namespace Flute\Modules\BansComms\Driver\Items\IKS\ColumnManager;

use Flute\Core\Table\TableBuilder;
use Flute\Core\Table\TableColumn;
use Flute\Modules\BansComms\Driver\Items\IKS\Contracts\ColumnFormatterInterface;

class TableColumnManager
{
    private ColumnFormatterInterface $formatter;

    public function __construct(ColumnFormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    public function getCommsColumns(TableBuilder $tableBuilder)
    {
        $tableBuilder->addColumn((new TableColumn('source', __('banscomms.table.type')))
            ->setRender("{{ICON_TYPE}}", $this->formatter->typeFormatRender()));

        $tableBuilder->addColumn((new TableColumn('user_url'))->setVisible(false));
        $tableBuilder->addCombinedColumn('avatar', 'name', __('banscomms.table.loh'), 'user_url', true);

        $tableBuilder->addColumns([
            (new TableColumn('created', __('banscomms.table.created')))->setDefaultOrder()
                ->setRender("{{CREATED}}", $this->formatter->dateFormatRender()),
            (new TableColumn('reason', __('banscomms.table.reason')))->setType('text'),
        ]);

        $tableBuilder->addColumn((new TableColumn('admin_url'))->setVisible(false));
        $tableBuilder->addCombinedColumn('admin_avatar', 'admin_name', __('banscomms.table.admin'), 'admin_url', true);

        $tableBuilder->addColumns([
            (new TableColumn('end', __('banscomms.table.end_date')))->setType('text')
                ->setRender("{{ENDS}}", $this->formatter->dateFormatRender()),
            (new TableColumn('time', ''))->setType('text')->setVisible(false),
            (new TableColumn('Unbanned', ''))->setType('text')->setVisible(false),
            (new TableColumn('UnbannedBy', ''))->setType('text')->setVisible(false),
            (new TableColumn('', __('banscomms.table.length')))
                ->setSearchable(false)->setOrderable(false)
                ->setRender('{{KEY}}', $this->formatter->timeFormatRender()),
        ]);
    }

    public function getBansColumns(TableBuilder $tableBuilder)
    {
        $tableBuilder->addColumn((new TableColumn('user_url'))->setVisible(false));
        $tableBuilder->addCombinedColumn('avatar', 'name', __('banscomms.table.loh'), 'user_url', true);

        $tableBuilder->addColumns([
            (new TableColumn('created', __('banscomms.table.created')))->setDefaultOrder()
                ->setRender("{{CREATED}}", $this->formatter->dateFormatRender()),
            (new TableColumn('reason', __('banscomms.table.reason')))->setType('text'),
        ]);

        $tableBuilder->addColumn((new TableColumn('admin_url'))->setVisible(false));
        $tableBuilder->addCombinedColumn('admin_avatar', 'admin_name', __('banscomms.table.admin'), 'admin_url', true);

        $tableBuilder->addColumns([
            (new TableColumn('end', __('banscomms.table.end_date')))->setType('text')
                ->setRender("{{ENDS}}", $this->formatter->dateFormatRender()),
            (new TableColumn('time', ''))->setType('text')->setVisible(false),
            (new TableColumn('Unbanned', ''))->setType('text')->setVisible(false),
            (new TableColumn('UnbannedBy', ''))->setType('text')->setVisible(false),
            (new TableColumn('', __('banscomms.table.length')))
                ->setSearchable(false)->setOrderable(false)
                ->setRender('{{KEY}}', $this->formatter->timeFormatRenderBans()),
        ]);
    }
}
