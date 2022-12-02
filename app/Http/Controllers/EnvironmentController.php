<?php

namespace App\Http\Controllers;


use App\gLibraries\guid;
use App\gLibraries\gvalidate;
use App\gLibraries\gjson;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Response;
use App\Models\Environment;
use App\Models\Module;
use App\Models\View;
use Exception;

class EnvironmentController extends Controller
{
    public function index(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'environments', 'read')) {
                throw new Exception('No tienes permisos para listar los roles del sistema');
            }

            $environmentsJpa = Environment::all()
            ->whereNotNull('status');
            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setData($environmentsJpa->toArray());
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

    public function paginate(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'environments', 'read')) {
                throw new Exception('No tienes permisos para listar los roles del sistema');
            }

            $query = Environment::orderBy($request->order['column'], $request->order['dir']);
            
            if (!$request->all || !gValidate::check($role->permissions, 'environments', 'see_trash')) {
                $query->whereNotNull('status');
            }
            $query->where(function ($q) use ($request) {
                $column = $request->search['column'];
                $type = $request->search['regex'] ? 'like' : '=';
                $value = $request->search['value'];
                $value = $type == 'like' ? DB::raw("'%{$value}%'") : $value;
                if ($column == 'environment' || $column == '*') {
                    $q->where('environment', $type, $value);
                }
                if ($column == 'domain' || $column == '*') {
                    $q->orWhere('domain', $type, $value);
                }
                if ($column == 'description' || $column == '*') {
                    $q->orWhere('description', $type, $value);
                }
            });

            $iTotalDisplayRecords = $query->count();
            $environmentsJpa = $query->select('*')
                ->skip($request->start)
                ->take($request->length)
                ->get();

            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setDraw($request->draw);
            $response->setITotalDisplayRecords($iTotalDisplayRecords);
            $response->setITotalRecords(Environment::count());
            $response->setData($environmentsJpa->toArray());
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

    public function store(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'environments', 'create')) {
                throw new Exception('No tienes permisos para listar los roles del sistema');
            }

            if (
                !isset($request->environment)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            $environmentValidation = Environment::select(['environments.environment'])
                ->where('environment', $request->environment)
                ->first();
            if ($environmentValidation) {

                if ($environmentValidation->environment == $request->environment) {
                    throw new Exception("Escoja otro nombre para el ambiente");
                }
            }

            $environmentJpa = new Environment();
            $environmentJpa->environment = $request->environment;
            $environmentJpa->domain = $request->domain;
            $environmentJpa->description = $request->description;
            $environmentJpa->save();

            $response->setStatus(200);
            $response->setMessage('El ambiente se a agregado correctamente');
            $response->setData($environmentJpa->toArray());
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

    public function update(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'environments', 'update')) {
                throw new Exception('No tienes permisos para listar los roles del sistema');
            }

            if (
                !isset($request->id) ||
                !isset($request->environment) ||
                !isset($request->status)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            $environmentValidation = Environment::select(['environments.id', 'environments.environment'])
                ->where('environment', $request->environment)
                ->where('id', '!=', $request->id)
                ->first();
            if ($environmentValidation) {
                if ($environmentValidation->view == $request->view) {
                    throw new Exception("Escoja otro nombre para el ambiente");
                }
            }

            $environmentJpa = Environment::find($request->id);
            if (!$environmentJpa) {
                throw new Exception("No se puede actualizar este ambiente");
            }
            $environmentJpa->environment = $request->environment;
            $environmentJpa->domain = $request->domain;
            $environmentJpa->description = $request->description;
            $environmentJpa->status = $request->status;
            $environmentJpa->save();

            $response->setStatus(200);
            $response->setMessage('El ambiente se a actualizado correctamente');
            $response->setData($environmentJpa->toArray());
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

    public function delete(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'environments', 'delete_restore')) {
                throw new Exception('No tienes permisos para listar los roles del sistema');
            }

            if (
                !isset($request->id)
            ) {
                throw new Exception("Error: Es necesario el ID para esta operación");
            }

            $environmentJpa = Environment::find($request->id);

            if (!$environmentJpa) {
                throw new Exception("Este reguistro no existe");
            }

            $environmentJpa->status = null;
            $environmentJpa->save();

            $response->setStatus(200);
            $response->setMessage('El ambiente se a eliminado correctamente');
            $response->setData($environmentJpa->toArray());
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

    public function restore(Request $request)
    {
        $response = new Response();
        try {
            
            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'environments', 'delete_restore')) {
                throw new Exception('No tienes permisos para listar los roles del sistema');
            }

            if (
                !isset($request->id)
            ) {
                throw new Exception("Error: Es necesario el ID para esta operación");
            }

            $environmentJpa = Environment::find($request->id);
            if (!$environmentJpa) {
                throw new Exception("Este reguistro no existe");
            }
            $environmentJpa->status = "1";
            $environmentJpa->save();

            $response->setStatus(200);
            $response->setMessage('El ambiente a sido restaurado correctamente');
            $response->setData($environmentJpa->toArray());
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
