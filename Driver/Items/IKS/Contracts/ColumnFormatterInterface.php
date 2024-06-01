<?php

namespace Flute\Modules\BansComms\Driver\Items\IKS\Contracts;

interface ColumnFormatterInterface
{
    public function dateFormatRender(): string;
    public function typeFormatRender(): string;
    public function timeFormatRender(): string;
    public function timeFormatRenderBans(): string;
}
