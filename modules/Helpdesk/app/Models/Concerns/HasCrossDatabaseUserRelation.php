<?php

namespace Modules\Helpdesk\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasCrossDatabaseUserRelation
{
    /**
     * Create a BelongsTo relation to the User model on the default database connection.
     *
     * Use this when the User model lives on a different database than the current model
     * (e.g. helpdesk connection vs default mysql connection).
     *
     * @param  string  $foreignKey  The foreign key column on this model
     * @param  string  $relationName  The relation name
     */
    public function belongsToUser(string $foreignKey = 'user_id', string $relationName = 'user'): BelongsTo
    {
        $instance = (new User)->setConnection(null);

        return $this->newBelongsTo(
            $instance->newQuery(),
            $this,
            $foreignKey,
            'id',
            $relationName
        );
    }
}
