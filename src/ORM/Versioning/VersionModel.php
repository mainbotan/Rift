<?php

namespace Rift\Core\ORM\Versioning;

use Rift\Contracts\Models\ModelInterface;
use Rift\Core\ORM\Model;
use Rift\Core\ORM\Types;

class VersionModel extends Model
{
    const NAME = 'versions';
    const VERSION = '1.0.0';

    protected function schema(): void {
        $this->table->create('table_name')
            ->type(Types::varchar(64))
            ->nullable(false)
            ->affirm();

        $this->table->create('version')
            ->type(Types::varchar(32))
            ->affirm();
        
        $this->table->create('created_at')
            ->type(Types::CREATED_AT)
            ->affirm();

        $this->table->uniqueIndex(['table_name'])
            ->affirm();
    }
}