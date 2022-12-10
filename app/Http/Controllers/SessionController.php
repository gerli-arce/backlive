<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Response;
use App\gLibraries\guid;
use App\gLibraries\gjson;
use Illuminate\Http\Request;
use Exception;


class SessionController extends Controller
{
    public function login(Request $request)
    {
        $response = new Response();
        try {

            if (
                !isset($request->password) ||
                !isset($request->username)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            $userJpa = User::select([
                'users.id',
                'users.username',
                'users.password',
                'users.auth_token',
                'users.lastname',
                'users.name',
                'users.email',
                'users.phone_prefix',
                'users.phone_number',
                'users.status',
            ])
                ->where('username', $request->username)
                ->where('password', $request->password)
                ->first();

            if (!$userJpa) {
                throw new Exception('Error: Usuario no existe');
            }
            if (!$userJpa->status) {
                throw new Exception('Este usuario se encuentra inactivo');
            }
            

            $userJpa->auth_token = guid::long();
            $userJpa->save();

            $user = gJSON::restore($userJpa->toArray());
            unset($user['id']);
            unset($user['password']);
         

            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setData($user);
        } catch (\Throwable $th) {
            $response->setStatus(400);
            $response->setMessage($th->getMessage());
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }

    public function logout(Request $request)
    {
        $response = new Response();
        try {
            if (
                !isset($request->relative_id)
            ) {
                throw new Exception("Error: no deje campos vaciós");
            }
            if ($request->header('SoDe-Auth-Token') == null || $request->header('SoDe-Auth-User') == null) {
                throw new Exception('Error: Datos de cabesera deben ser enviados');
            }
            $userJpaValidation = User::select([
                'users.username',
                'users.auth_token'
            ])
            ->where('auth_token', $request->header('SoDe-Auth-Token'))
            ->where('username', $request->header('SoDe-Auth-User'))
            ->first();

            if (!$userJpaValidation) {
                throw new Exception('Error: Usted no puede realizar esta operación (SUS DATOS DE USUARIO SON INCORRECTOS)');
            }

            $userJpa = User::select([
                'users.id',
                'users.username',
                'users.auth_token'
            ])->where('relative_id', $request->relative_id)
            ->first();

            $userJpa ->auth_token = null;
            $userJpa ->save();

            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setData([]);
        } catch (\Throwable $th) {
            $response->setStatus(400);
            $response->setMessage($th->getMessage());
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }

    public function verify(Request $request)
    {
        $response = new Response();
        try {
            // if (
            //     $request->header('sode-auth-token') == null ||
            //     $request->header('sode-auth-user') == null
            // ) {
            //     throw new Exception('Debe enviar los parámetros necesarios');
            // }

            $userJpa = User::select([
                'users.relative_id',
                'users.username',
                'users.auth_token',
                'users.lastname',
                'users.name',
                'users.email',
                'users.phone_prefix',
                'users.phone_number',
                'users.status',
            ])
                ->where('auth_token', $request->header('sode-auth-token'))
                ->where('username', $request->header('sode-auth-user'))
                ->first();

            if (!$userJpa) {
                throw new Exception('No tienes una sesión activa');
            }

            $user = gJSON::restore($userJpa->toArray());

            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setData($user);
        } catch (\Throwable $th) {
            $response->setStatus(400);
            $response->setMessage($th->getMessage());
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }
}
