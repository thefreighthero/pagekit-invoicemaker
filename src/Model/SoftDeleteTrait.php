<?php

namespace Bixie\Invoicemaker\Model;

use Pagekit\Application as App;
use Pagekit\Database\ORM\ModelTrait;
use Pagekit\Database\ORM\QueryBuilder;

trait SoftDeleteTrait {

    /**
     * Creates a new QueryBuilder instance.
     *
     * @return QueryBuilder
     */
    public static function query()
    {
        $query = new QueryBuilder(static::getManager(), static::getMetadata());

        $query = $query->where('deleted_at IS NULL');

        return $query;
    }


    public function delete()
    {
        // Check if the model is already soft-deleted
        if ($this->isSoftDeleted()) {
            // Optionally, you can handle already deleted cases here
            return;
        }

        $this->deleted_at = new \DateTime();
        $this->save();
    }

    public function restore()
    {
        $this->deleted_at = null;
        $this->save();
    }

    public function isSoftDeleted()
    {
        return !is_null($this->deleted_at);
    }

    public function forceDelete()
    {
        parent::delete();
    }
}