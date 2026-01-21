<?php

namespace Ndx\SimpleRedirect\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $table = 'redirects';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('statamic.redirects.database.table', 'redirects'));

        if ($connection = config('statamic.redirects.database.connection')) {
            $this->setConnection($connection);
        }
    }

    protected function casts(): array
    {
        return [
            'regex'       => 'boolean',
            'enabled'     => 'boolean',
            'status_code' => 'integer',
            'order'       => 'integer',
        ];
    }
}
