<?php

namespace Dcat\Admin\Grid\Tools;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid;
use Dcat\Admin\Widgets\Checkbox;
use Illuminate\Support\Collection;

class ColumnSelector extends AbstractTool
{
    const SELECT_COLUMN_NAME = '_columns_';

    /**
     * @var Grid
     */
    protected $grid;

    /**
     * @var array
     */
    protected static $ignoredColumns = [
        Grid\Column::SELECT_COLUMN_NAME,
        Grid\Column::ACTION_COLUMN_NAME,
    ];

    /**
     * Create a new Export button instance.
     *
     * @param Grid $grid
     */
    public function __construct(Grid $grid)
    {
        $this->grid = $grid;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function render()
    {
        if (!$this->grid->showColumnSelector()) {
            return '';
        }

        $show = $this->grid->visibleColumnNames();

        $list = new Checkbox();

        $list->class('column-select-item');
        $list->options($this->getGridColumns());
        $list->check(
            $this->getGridColumns()->filter(function ($label, $key) use ($show) {
                if (empty($show)) {
                    return true;
                }

                return in_array($key, $show) ? true : false;
            })->keys()
        );

        $btns = [
            'all'    => trans('admin.all'),
            'submit' => trans('admin.submit'),
        ];

        $this->setupScript();

        return <<<EOT

<div class="dropdown pull-right column-selector" style="margin-right: 10px">
    <button type="button" class="btn btn-sm btn-instagram dropdown-toggle" data-toggle="dropdown">
        <i class="fa fa-table"></i>
        &nbsp;
        <span class="caret"></span>
    </button>
    <ul class="dropdown-menu" role="menu" style="padding: 10px;height: auto;max-height: 500px;overflow-x: hidden;">
        <li>
            <ul style='padding: 0;'>
                {$list->render()}
            </ul>
        </li>
        <li class="divider"></li>
        <li class="text-right">
            <button class="btn btn-sm btn-default column-select-all">{$btns['all']}</button>&nbsp;&nbsp;
            <button class="btn btn-sm btn-primary column-select-submit">{$btns['submit']}</button>
        </li>
    </ul>
</div>
EOT;
    }

    /**
     * @return Collection
     */
    protected function getGridColumns()
    {
        return $this->grid->columns()->map(function (Grid\Column $column) {
            $name = $column->getName();

            if ($this->isColumnIgnored($name)) {
                return;
            }

            return [$name => $column->getLabel()];
        })->filter()->collapse();
    }

    /**
     * Is column ignored in column selector.
     *
     * @param string $name
     *
     * @return bool
     */
    protected function isColumnIgnored($name)
    {
        return in_array($name, static::$ignoredColumns);
    }

    /**
     * Ignore a column to display in column selector.
     *
     * @param string|array $name
     */
    public static function ignore($name)
    {
        static::$ignoredColumns = array_merge(static::$ignoredColumns, (array) $name);
    }

    /**
     * Setup script.
     */
    protected function setupScript()
    {
        $defaults = json_encode($this->grid->getDefaultVisibleColumnNames());

        $script = <<<JS

$('.column-select-submit').on('click', function () {
    
    var defaults = $defaults;
    var selected = [];
    
    $('.column-select-item:checked').each(function () {
        selected.push($(this).val());
    });

    if (selected.length == 0) {
        return;
    }

    var url = new URL(location);
    
    if (selected.sort().toString() == defaults.sort().toString()) {
        url.searchParams.delete('_columns_');
    } else {
        url.searchParams.set('_columns_', selected.join());
    }

    $.pjax({container:'#pjax-container', url: url.toString()});
});

$('.column-select-all').on('click', function () {
    $('.column-select-item').iCheck('check');
    return false;
});

$('.column-select-item').iCheck({
    checkboxClass:'icheckbox_minimal-blue'
});

JS;

        Admin::script($script);
    }
}
