<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Edicao extends Model
{
    protected $table = 'edicao';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'destino',
        'ano'
    ];

    protected $guarded = ['id'];

    const CREATED_AT = 'criado';
    const UPDATED_AT = 'modificado';

    public function fotos()
    {
        return $this->hasMany(Foto::class);
    }
}
