<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Programa extends Model
{
    protected $table = 'programa';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'titulo',
        'descricao',
        'data_inicio',
        'data_final',
        'regulamento',
        'regulamento_arquivo',
        'termo_adesao'
    ];

    protected $guarded = ['id'];
}
