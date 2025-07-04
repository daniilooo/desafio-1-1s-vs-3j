<?php

namespace Rn;

require_once __DIR__ . '/../../vendor/autoload.php';

use Models\Usuario;
use Models\Projeto;
use Models\Equipe;
use Models\Log;
use Utils\JsonServices;

class RnUsuarios{

    function superUsers() {
        $json = JsonServices::lerUltimoJson();
        $listaUsuarios = [];
    
        foreach ($json as $item) {
            $listaProjetos = [];
            $listaLogs = [];            
    
            // 1. Projetos da equipe
            if (isset($item['equipe']['projetos']) && is_array($item['equipe']['projetos'])) {
                foreach ($item['equipe']['projetos'] as $proj) {
                    $projeto = new Projeto($proj['nome'], $proj['concluido']);                   

                    $listaProjetos[] = $projeto;
                }
            }            
    
            // 2. Equipe
            $equipeData = $item['equipe'];
            $equipe = new Equipe($equipeData['nome'], $equipeData['lider'], $listaProjetos);
    
            // 3. Logs
            if (isset($item['logs']) && is_array($item['logs'])) {
                foreach ($item['logs'] as $logData) {
                    $log = new Log($logData['data'], $logData['acao']);
                    $listaLogs[] = $log;
                }
            }
    
            // 4. Criando o usuário
            $usuario = new Usuario(
                $item['id'],
                $item['nome'],
                $item['idade'],
                $item['score'],
                $item['ativo'],
                $item['pais'],
                $equipe,
                $listaLogs
            );
    
            // 5. Filtro (score >= 900 e ativo)
            if ($usuario->getScore() >= 900 && $usuario->getAtivo()) {
                $listaUsuarios[] = $usuario;
            }
        }
    
        return $listaUsuarios;
    }


    function quantidadeSuperUsuariosPorPais() {
        $usuariosAgrupados = $this->agruparUsuariosPorPais();

        $quantidadePorPais = [];

        foreach($usuariosAgrupados as $item){
            $pais = $item['pais'];
            
            $quantidade = count($item['usuarios']);

            $quantidadePorPais[] = [
                'pais' => $pais,
                'quantidade' => $quantidade
            ];
        }

        return $quantidadePorPais;

    }

    function agruparUsuariosPorPais(){
        $json = JsonServices::lerUltimoJson();    
        $usuariosPorPais = [];    
        
        foreach ($json as $usuario) {
            $pais = $usuario['pais'];
    
            if (!isset($usuariosPorPais[$pais])) {
                $usuariosPorPais[$pais] = [];
            }
    
            $usuariosPorPais[$pais][] = $usuario;
        }    
        
        $listaUsuariosAgrupados = [];
    
        foreach ($usuariosPorPais as $pais => $usuarios) {
            $listaUsuariosAgrupados[] = [
                'pais' => $pais,
                'usuarios' => $usuarios
            ];
        }
    
        return $listaUsuariosAgrupados;
    }

    function agruparPorEquipe(){
        $json = JsonServices::lerUltimoJson();        
        $relatorioFinal = [];        

        foreach ($json as $usuario) {
            $equipe = $usuario['equipe']['nome'];   
    
            if (!isset($relatorioFinal[$equipe])) {
                $relatorioFinal[$equipe] = [
                    'equipe' => $equipe,
                    'quantidade' => 0,
                    'lideres' => 1,
                    'projetosConcluidos' => 1,
                    'ativos' => 0, 
                    'percentualMembros' => 0
                ];
            }

            $relatorioFinal[$equipe]['quantidade']++;

            if (!empty($usuario['ativo'])) {
                $relatorioFinal[$equipe]['ativos']++;
            }

            if($usuario['equipe']['lider'] == true){
                $relatorioFinal[$equipe]['lideres']++;
            }    
            
            foreach($usuario['equipe']['projetos'] as $projeto){
                if($projeto['concluido']){
                    $relatorioFinal[$equipe]['projetosConcluidos']++;
                }
            }            
        }

        foreach ($relatorioFinal as &$equipe) {
            if ($equipe['quantidade'] > 0) {
                $equipe['percentualMembros'] = round(($equipe['ativos'] / $equipe['quantidade']) * 100, 2);
            }
            unset($equipe['ativos']); // remove o campo auxiliar
        }       




        return $relatorioFinal;
    }
    
    

}


?>