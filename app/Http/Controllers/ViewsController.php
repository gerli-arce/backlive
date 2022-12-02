<?php

namespace App\Http\Controllers;

use App\Models\View;
use App\gLibraries\gvalidate;
use App\gLibraries\gjson;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Response;
use Exception;

class ViewsController extends Controller
{
    public function index(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'views', 'read')) {
                throw new Exception('No tienes permisos para listar las vistas del sistema');
            }

            $viewsJpa = View::whereNotNull('status')->orderBy('view', 'ASC')->get();
            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setData($viewsJpa->toArray());
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
            if (!gValidate::check($role->permissions, 'views', 'read')) {
                throw new Exception('No tienes permisos para listar las vistas del sistema');
            }

            $query = View::orderBy($request->order['column'], $request->order['dir']);

            if (!$request->all || !gValidate::check($role->permissions, 'views', 'see_trash')) {
                $query->whereNotNull('status');
            }
            $query->where(function ($q) use ($request) {
                $column = $request->search['column'];
                $type = $request->search['regex'] ? 'like' : '=';
                $value = $request->search['value'];
                $value = $type == 'like' ? DB::raw("'%{$value}%'") : $value;
                if ($column == 'view' || $column == '*') {
                    $q->where('view', $type, $value);
                }
                if ($column == 'path' || $column == '*') {
                    $q->orWhere('path', $type, $value);
                }
                if ($column == 'description' || $column == '*') {
                    $q->orWhere('description', $type, $value);
                }
            });
            $iTotalDisplayRecords = $query->count();
            $viewsJpa = $query->select('*')
                ->skip($request->start)
                ->take($request->length)
                ->get();
            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setDraw($request->draw);
            $response->setITotalDisplayRecords($iTotalDisplayRecords);
            $response->setITotalRecords(View::count());
            $response->setData($viewsJpa->toArray());
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
            if (!gValidate::check($role->permissions, 'views', 'create')) {
                throw new Exception('No tienes permisos para crear vistas en el sistema');
            }

            if (
                !isset($request->view) ||
                !isset($request->path) ||
                !isset($request->placement)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            $viewValidation = View::select(['views.view', 'views.path'])
                ->where('view', $request->view)
                ->orWhere('path', $request->path)
                ->first();
            if ($viewValidation) {

                if ($viewValidation->view == $request->view) {
                    throw new Exception("Escoja otro nombre para la vista");
                }

                if ($viewValidation->path == $request->path) {
                    throw new Exception("Escoja otra ruta para la vista");
                }
            }

            $viewJpa = new view();
            $viewJpa->view = $request->view;
            $viewJpa->path = $request->path;
            $viewJpa->placement = $request->placement;

            if (isset($request->description))
                $viewJpa->description = $request->description;

            $viewJpa->save();

            $response->setStatus(200);
            $response->setMessage('El view se a agregado correctamente');
            $response->setData($viewJpa->toArray());
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
            if (!gValidate::check($role->permissions, 'views', 'update')) {
                throw new Exception('No tienes permisos para actualizar vistas en el sistema');
            }

            if (
                !isset($request->id) ||
                !isset($request->view) ||
                !isset($request->placement) ||
                !isset($request->path)

            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            $viewValidation = View::select(['views.view', 'views.path'])
                ->where('id', '!=', $request->id)
                ->where(function ($q) use ($request) {
                    $q->where('view', $request->view);
                    $q->orWhere('path', $request->path);
                })
                ->first();
            if ($viewValidation) {
                if ($viewValidation->view == $request->view) {
                    throw new Exception("Escoja otro nombre para la vista");
                }
                if ($viewValidation->path == $request->path) {
                    throw new Exception("Escoja otra ruta para esta vista");
                }
            }

            $viewJpa = View::find($request->id);
            if (!$viewJpa) {
                throw new Exception("La vista solicitada no existe");
            }
            $viewJpa->view = $request->view;
            $viewJpa->placement = $request->placement;
            $viewJpa->path = $request->path;
            if (isset($request->description))
                $viewJpa->description = $request->description;
            if (gValidate::check($role->permissions, 'views', 'change_status'))
                if (isset($request->status))
                    $viewJpa->status = $request->status;

            $viewJpa->save();

            $response->setStatus(200);
            $response->setMessage('La vista se a actualizado correctamente');
            $response->setData($viewJpa->toArray());
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
            if (!gValidate::check($role->permissions, 'views', 'delete_restore')) {
                throw new Exception('No tienes permisos para eliminar las vistas del sistema');
            }

            if (
                !isset($request->id)
            ) {
                throw new Exception("Error: Es necesario el ID para esta operación");
            }

            $viewJpa = View::find($request->id);

            if (!$viewJpa) {
                throw new Exception("Este reguistro no existe");
            }

            $viewJpa->status = null;
            $viewJpa->save();

            $response->setStatus(200);
            $response->setMessage('La vista se a eliminado correctamente');
            $response->setData($viewJpa->toArray());
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
            if (!gValidate::check($role->permissions, 'views', 'delete_restore')) {
                throw new Exception('No tienes permisos para restaurar las vistas del sistema');
            }

            if (
                !isset($request->id)
            ) {
                throw new Exception("Error: Es necesario el ID para esta operación");
            }

            $viewJpa = View::find($request->id);
            if (!$viewJpa) {
                throw new Exception("Este reguistro no existe");
            }
            $viewJpa->status = "1";
            $viewJpa->save();

            $response->setStatus(200);
            $response->setMessage('La vsita a sido restaurado correctamente');
            $response->setData($viewJpa->toArray());
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
