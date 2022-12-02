<?php

namespace App\Http\Controllers;

use App\gLibraries\gjson;
use App\gLibraries\gvalidate;
use App\Models\Response;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PermissionController extends Controller
{


    public function index(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (
                !gValidate::check($role->permissions, 'permissions', 'read') &&
                !gValidate::check($role->permissions, 'roles', 'read')
            ) {
                throw new Exception('No tienes permisos para listar los permisos del sistema');
            }

            $permissionsJpa = Permission::select([
                'permissions.id',
                'permissions.permission',
                'permissions.correlative',
                'permissions.description',
                'views.id AS view.id',
                'views.view AS view.view',
                'views.placement AS view.placement',
                'views.path AS view.path',
                'views.description AS view.description',
                'views.status AS view.status',
                'permissions.status'

            ])
                ->leftjoin('views', 'permissions._view', '=', 'views.id')
                ->whereNotNull('permissions.status')
                ->orderBy('views.placement', 'asc')
                ->get();

            $permissions = array();
            foreach ($permissionsJpa as $permissionJpa) {
                $permission = gJSON::restore($permissionJpa->toArray());
                $permissions[] = $permission;
            }
            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setData($permissions);
        } catch (\Throwable $th) {
            $response->setMessage($th->getMessage());
            $response->setStatus(400);
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
            if (
                !gValidate::check($role->permissions, 'permissions', 'read') &&
                !gValidate::check($role->permissions, 'roles', 'read')
            ) {
                throw new Exception('No tienes permisos para listar los permisos del sistema');
            }

            $query = Permission::select([
                'permissions.id',
                'permissions.permission',
                'permissions.correlative',
                'permissions.description',
                'views.id AS view.id',
                'views.view AS view.view',
                'views.path AS view.path',
                'views.description AS view.description',
                'views.status AS view.status',
                'permissions.status'

            ])
                ->leftjoin('views', 'permissions._view', '=', 'views.id')
                ->orderBy('permissions.' . $request->order['column'], $request->order['dir']);

            if (!$request->all) {
                $query->whereNotNull('permissions.status');
            }

            $query->where(function ($q) use ($request) {
                $column = $request->search['column'];
                $type = $request->search['regex'] ? 'like' : '=';
                $value = $request->search['value'];
                $value = $type == 'like' ? DB::raw("'%{$value}%'") : $value;
                if ($column == 'permission' || $column == '*') {
                    $q->where('permission', $type, $value);
                }
                if ($column == '_view' || $column == '*') {
                    $q->orWhere('views.view', $type, $value);
                }
                if ($column == 'correlative' || $column == '*') {
                    $q->orWhere('permissions.correlative', $type, $value);
                }
                if ($column == 'description' || $column == '*') {
                    $q->orWhere('permissions.description', $type, $value);
                }
            });

            $iTotalDisplayRecords = $query->count();
            $permissionsJpa = $query
                ->skip($request->start)
                ->take($request->length)
                ->get();


            $permissions = array();
            foreach ($permissionsJpa as $permissionJpa) {
                $permission = gJSON::restore($permissionJpa->toArray());
                $permissions[] = $permission;
            }
            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setDraw($request->draw);
            $response->setITotalDisplayRecords($iTotalDisplayRecords);
            $response->setITotalRecords(Permission::count());
            $response->setData($permissions);
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
            if (!gValidate::check($role->permissions, 'permissions', 'create')) {
                throw new Exception('No tienes permisos para agregar permisos en el sistema');
            }

            if (
                !isset($request->permission) ||
                !isset($request->correlative) ||
                !isset($request->_view)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }
            $PermissionValidation = Permission::select(['permissions.permission'])->where('permission', $request->permission)->first();

            if ($PermissionValidation) {
                throw new Exception("Este permiso ya existe");
            }

            $permissionJpa = new Permission();
            $permissionJpa->permission = $request->permission;
            $permissionJpa->correlative = $request->correlative;
            $permissionJpa->_view = $request->_view;
            $permissionJpa->description = $request->description;
            $permissionJpa->save();

            $response->setStatus(200);
            $response->setMessage('Permiso agregado correctamente');
            $response->setData($permissionJpa->toArray());
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
            if (!gValidate::check($role->permissions, 'permissions', 'update')) {
                throw new Exception('No tienes permisos para modificar permisos en el sistema');
            }

            if (
                !isset($request->permission) ||
                !isset($request->correlative) ||
                !isset($request->description) ||
                !isset($request->_view) ||
                !isset($request->status) ||
                !isset($request->id)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            $PermissionValidation = Permission::select(['permissions.id', 'permissions.permission'])
                ->where('permission', $request->permission)
                ->where('id', '!=', $request->id)
                ->first();

            if ($PermissionValidation) {
                throw new Exception("Este permiso ya existe");
            }

            $permissionJpa = Permission::find($request->id);
            $permissionJpa->permission = $request->permission;
            $permissionJpa->correlative = $request->correlative;
            $permissionJpa->_view = $request->_view;
            $permissionJpa->description = $request->description;
            $permissionJpa->status = $request->status;
            $permissionJpa->save();

            $response->setStatus(200);
            $response->setMessage('Permiso actualizado correctamente');
            $response->setData($permissionJpa->toArray());
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
            if (!gValidate::check($role->permissions, 'permissions', 'delete_restore')) {
                throw new Exception('No tienes permisos para eliminar permisos en el sistema');
            }

            if (
                !isset($request->id)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            $permissionJpa = Permission::find($request->id);
            $permissionJpa->status = null;
            $permissionJpa->save();

            $response->setStatus(200);
            $response->setMessage('Permiso elimidado correctamente');
            $response->setData($permissionJpa->toArray());
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
            if (!gValidate::check($role->permissions, 'permissions', 'delete_restore')) {
                throw new Exception('No tienes permisos para restablecer permisos en el sistema');
            }

            if (
                !isset($request->id)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            $permissionJpa = Permission::find($request->id);
            $permissionJpa->status = "1";
            $permissionJpa->save();

            $response->setStatus(200);
            $response->setMessage('Permiso restaurado correctamente');
            $response->setData($permissionJpa->toArray());
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
