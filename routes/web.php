<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// $router->get('/key', function() {
//     return 'APP_KEY=base64:'. base64_encode(\Illuminate\Support\Str::random(32));
// });

$router->get('/programa/data', 'ProgramaController@getData');

$router->post('/login', 'UsuariosController@login');
$router->post('/logout', 'UsuariosController@logout');

$router->group(['prefix' => '/senha'], function () use ($router) {
    $router->post('/redefinir', 'SenhaController@resetSenha');
    $router->get('/verificar-token/{token}', 'SenhaController@getToken');
    $router->put('/atualizar/{token}', 'SenhaController@UpdateSenha');
});

$router->group(['prefix' => '/cadastro'], function () use ($router) {
    $router->get('/usuario/{token}', 'CadastroController@getUsuario');
    $router->post('/finalizar/{token}', 'CadastroController@finalizar');
});

$router->group(['prefix' => '/painel', 'middleware' => 'participante'], function () use ($router) {
    $router->get('/usuario', 'UsuariosController@getUsuario');
    $router->put('/usuario/atualizar', 'UsuariosController@updateUsuario');

    $router->get('/ranking', 'RankingController');
});

$router->group(['prefix' => '/manager'], function () use ($router) {
    $router->post('/login', 'Manager\UsuariosController@login');
    $router->post('/logout', 'Manager\UsuariosController@logout');

    $router->group(['middleware' => 'admin'], function () use ($router) {
        $router->get('/usuario', 'Manager\UsuariosController@getUsuario');

        $router->get('/stats', 'Manager\ProgramaController@getStats');
        $router->get('/data', 'Manager\ProgramaController@getData');
        $router->post('/data', 'Manager\ProgramaController@postData');

        $router->get('/participantes', 'Manager\ParticipantesController@getParticipantes');
        $router->post('/participantes/convidar', 'Manager\ParticipantesController@inviteParticipante');
        $router->get('/participantes/{id}', 'Manager\ParticipantesController@getParticipante');
        $router->post('/participantes/atualizar/{id}', 'Manager\ParticipantesController@updateParticipante');
        $router->delete('/participantes/excluir/{ids}', 'Manager\ParticipantesController@deleteParticipantes');
        $router->post('/participantes/ativar/{ids}', 'Manager\ParticipantesController@activeParticipantes');
        $router->post('/participantes/desativar/{ids}', 'Manager\ParticipantesController@deactiveParticipantes');

        $router->post('/participantes/pontos/novo/{id}/{tipo}', 'Manager\PontosController@createPonto');
        $router->post('/participantes/pontos/atualizar/{id}', 'Manager\PontosController@updatePonto');
        $router->delete('/participantes/pontos/{ids}', 'Manager\PontosController@deletePontos');

        $router->group(['prefix' => '/relatorios'], function () use ($router) {
            $router->get('/participantes/geral', 'Manager\RelatoriosController@getParticipantes');
            $router->get('/participantes/export', 'Manager\RelatoriosController@exportParticipantes');

            $router->get('/participantes/documentos', 'Manager\RelatoriosController@getParticipantesDocs');
            $router->get('/participantes/documentos/export', 'Manager\RelatoriosController@exportParticipantesDocs');
        });

        $router->group(['prefix' => '/edicoes'], function () use ($router) {
            $router->get('/', 'Manager\EdicoesController@getEdicoes');
            $router->get('/{id}', 'Manager\EdicoesController@getEdicao');
            $router->post('/novo', 'Manager\EdicoesController@createEdicao');
            $router->put('/atualizar/{id}', 'Manager\EdicoesController@updateEdicao');
            $router->put('/visibilidade/{id}', 'Manager\EdicoesController@visibleEdicao');
            $router->put('/ordenar', 'Manager\EdicoesController@orderEdicao');
            $router->delete('/excluir/{id}', 'Manager\EdicoesController@deleteEdicao');

            $router->get('/{idEdicao}/fotos', 'Manager\FotosController@getFotos');
            $router->post('/{idEdicao}/fotos/novo', 'Manager\FotosController@createFoto');

            $router->put('fotos/ordenar', 'Manager\FotosController@orderFoto');
            $router->get('/fotos/{idFoto}', 'Manager\FotosController@getFoto');
            $router->put('/fotos/visibilidade/{idFoto}', 'Manager\FotosController@visibleFoto');
            $router->delete('/fotos/excluir/{idFoto}', 'Manager\FotosController@deleteFoto');
        });

        $router->get('/usuarios', 'Manager\UsuariosController@getUsuarios');
        $router->post('/usuarios/novo', 'Manager\UsuariosController@createUsuario');
        $router->get('/usuarios/{id}', 'Manager\UsuariosController@getUsuario');
        $router->post('/usuarios/atualizar/{id}', 'Manager\UsuariosController@updateUsuario');
        $router->delete('/usuarios/excluir/{ids}', 'Manager\UsuariosController@deleteUsuarios');
    });
});
