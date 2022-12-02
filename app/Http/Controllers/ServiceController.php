<?php

namespace App\Http\Controllers;

use App\gLibraries\gvalidate;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Response;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Exception;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $response = new Response();
        try {
            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'services', 'read')) {
                throw new Exception('No tienes permisos para listar los servicios');
            }

            $servicesJpa = Service::all();
            if (!$servicesJpa) {
                throw new Exception("Sin datos para mostrar");
            }

            $response->setStatus(200);
            $response->setMessage("Operación correcta");
            $response->setData($servicesJpa->toArray());
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
            if (!gValidate::check($role->permissions, 'services', 'read')) {
                throw new Exception('No tienes permisos para listar los servicios');
            }

            $query = Service::orderBy($request->order['column'], $request->order['dir']);

            if (!$request->all || !gValidate::check($role->permissions, 'services', 'see_trash')) {
                $query->whereNotNull('status');
            }
            $query->where(function ($q) use ($request) {
                $column = $request->search['column'];
                $type = $request->search['regex'] ? 'like' : '=';
                $value = $request->search['value'];
                $value = $type == 'like' ? DB::raw("'%{$value}%'") : $value;
                if ($column == 'service' || $column == '*') {
                    $q->where('service', $type, $value);
                }
                if ($column == 'correlative' || $column == '*') {
                    $q->orWhere('correlative', $type, $value);
                }
                if ($column == 'repository' || $column == '*') {
                    $q->orWhere('repository', $type, $value);
                }
            });
            $iTotalDisplayRecords = $query->count();
            $servicesJpa = $query->select('*')
                ->skip($request->start)
                ->take($request->length)
                ->get();
            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setDraw($request->draw);
            $response->setITotalDisplayRecords($iTotalDisplayRecords);
            $response->setITotalRecords(Service::count());
            $response->setData($servicesJpa->toArray());
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
            if (
                !isset($request->service) ||
                !isset($request->correlative) ||
                !isset($request->repository)
            ) {
                throw new Exception("Error: No dejes campos vacíos");
            }
            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'services', 'create')) {
                throw new Exception('No tienes permisos para agregar servicios');
            }

            $serviceValidation = Service::select(
                [
                    'services.service',
                    'services.correlative',
                    'services.repository'

                ]
            )->where('service', $request->service)
                ->orwhere('correlative', $request->correlative)
                ->orwhere('repository', $request->repository)
                ->first();
            if ($serviceValidation) {
                if ($serviceValidation->service == $request->service) {
                    throw new Exception("Este servicio ya existe");
                } else if ($serviceValidation->correlative == $request->correlative) {
                    throw new Exception("Este correlativo ya existe");
                } else if ($serviceValidation->repository == $request->repository) {
                    throw new Exception("Este repositorio ya existe");
                }
            }

            $serviceJpa = new Service();
            $serviceJpa->service = $request->service;
            $serviceJpa->correlative = $request->correlative;
            $serviceJpa->repository = $request->repository;
            if ($request->description) {
                $serviceJpa->description = $request->description;
            }
            $serviceJpa->status = "1";
            $serviceJpa->save();

            $response->setStatus(200);
            $response->setMessage("Servicio agregado correctamente");
            $response->setData([$serviceJpa]);
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
            if (
                !isset($request->service) ||
                !isset($request->correlative) ||
                !isset($request->repository) ||
                !isset($request->status)
            ) {
                throw new Exception("Error: No dejes campos vacíos");
            }
            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'services', 'update')) {
                throw new Exception('No tienes permisos para actualizar servicios');
            }

            $serviceValidation = Service::select(
                [
                    'services.id',
                    'services.service',
                    'services.correlative',
                    'services.repository'

                ]
            )->where('service', $request->service)
                ->where('id', '!=', $request->id)
                ->orwhere('correlative', $request->correlative)
                ->where('id', '!=', $request->id)
                ->orwhere('repository', $request->repository)
                ->where('id', '!=', $request->id)
                ->first();
            if ($serviceValidation) {
                if ($serviceValidation->service == $request->service) {
                    throw new Exception("Este servicio ya existe");
                } else if ($serviceValidation->correlative == $request->correlative) {
                    throw new Exception("Este correlativo ya existe");
                } else if ($serviceValidation->repository == $request->repository) {
                    throw new Exception("Este repositorio ya existe");
                }
            }

            $serviceJpa = Service::find($request->id);
            $serviceJpa->service = $request->service;
            $serviceJpa->correlative = $request->correlative;
            $serviceJpa->repository = $request->repository;
            if ($request->description) {
                $serviceJpa->description = $request->description;
            }
            $serviceJpa->status = $request->status;
            $serviceJpa->save();

            $response->setStatus(200);
            $response->setMessage("Servicio actualizado correctamente");
            $response->setData([$serviceJpa]);
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
            if (!isset($request->id)) {
                throw new Exception("El ID debe ser enviado");
            }

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'services', 'delete_restore')) {
                throw new Exception('No tienes permisos para eliminar servicios');
            }

            $service = Service::find($request->id);
            if (!$service) {
                throw new Exception("El registro no existe");
            }

            $service->status = null;
            $service->save();

            $response->setStatus(200);
            $response->setMessage("Servicio eliminado correctamente");
            $response->setData([$service]);
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
            if (!isset($request->id)) {
                throw new Exception("El ID debe ser enviado");
            }

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'services', 'delete_restore')) {
                throw new Exception('No tienes permisos para restaurar servicios');
            }

            $service = Service::find($request->id);
            if (!$service) {
                throw new Exception("El registro no existe");
            }

            $service->status = "1";
            $service->save();

            $response->setStatus(200);
            $response->setMessage("Servicio restaurado correctamente");
            $response->setData([$service]);
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
