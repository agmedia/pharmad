<?php

namespace App\Models\Front\Catalog\Options;

use Illuminate\Database\Eloquent\Model;

class OptionsTranslation extends Model
{

    /**
     * @var string
     */
    protected $table = 'options_translations';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

}
