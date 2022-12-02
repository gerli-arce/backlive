<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\gLibraries\gjson;
use App\gLibraries\gfetch;
use App\gLibraries\gtrace;
use App\Models\Response;
use Illuminate\Support\Facades\DB;
use App\gLibraries\gvalidate;
use App\Models\Activity;
use App\Models\Evidence;
use App\Models\View_activities;
use Exception;

class EvidenceController extends Controller
{

    public function index(Request $request, string $id)
    {
        $response = new Response();
        try {
            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'evidences', 'read')) {
                throw new Exception('No tienes para listar evidencias');
            }

            if (!$id) {
                throw new Exception('Se debe enviar un id de actividad');
            }

            $evidences = Evidence::select()
                ->where('_activity', $id)
                ->get();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. Lista de evidencias de la actividad');
            $response->setData($evidences->toArray());
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
            if (!gvalidate::check($role->permissions, 'evidences', 'create')) {
                throw new Exception('No tienes para listar evidencias');
            }

            if (
                !isset($request->file['type']) ||
                !isset($request->file['content']) ||
                !isset($request->caption) ||
                !isset($request->_activity)
            ) {
                throw new Exception('Error en los datos de entrada');
            }

            $req = $request->file;

            $evidenceJpa = new Evidence();
            $evidenceJpa->caption = $request->caption;
            $evidenceJpa->_activity = $request->_activity;
            $evidenceJpa->status = "1";

            $res = new gFetch("{$_ENV['FILES_URL']}/api/files", [
                'method' => 'POST',
                'body' => $req,
                'headers' => [
                    "Content-Type: application/json",
                    "SoDe-Auth-Token: {$_ENV['FILES_TOKEN']}",
                    "SoDe-Auth-Service: activity",
                ]
            ]);

            $data = $res->text();

            if (!$res->ok) {
                $data = gJSON::parseable($data) ? gJSON::parse($data) : [];
                throw new Exception($data['message'] ?? 'Error en ApiRest FILES, no hay respuesta del servidor');
            }

            $data = gJSON::parse($data);

            $evidenceJpa->file = $data['data']['file'];
            $evidenceJpa->save();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. Evidencia agregada con exito');
            $response->setData($res->json());
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
            if (!gvalidate::check($role->permissions, 'evidences', 'update')) {
                throw new Exception('No tienes para listar evidencias');
            }

            if (
                !isset($request->file['content']) ||
                !isset($request->caption) ||
                !isset($request->id)
            ) {
                throw new Exception('Error en los datos de entrada');
            }

            $evidenceJpa = Evidence::find($request->id);
            if (!$evidenceJpa) {
                throw new Exception("No se encontro evidencia");
            }


            if ($request->caption) {
                $evidenceJpa->caption = $request->caption;
            }

            if ($request->status) {
                $evidenceJpa->status = $request->status;
            }

            $req = [
                'file' => $evidenceJpa->file,
                'content' => $request->file['content'],
            ];
            $res = new gFetch("{$_ENV['FILES_URL']}/api/files", [
                'method' => 'PUT',
                'body' => $req,
                'headers' => [
                    "Content-Type: application/json",
                    "SoDe-Auth-Token: {$_ENV['FILES_TOKEN']}",
                    "SoDe-Auth-Service: activity",
                ]
            ]);

            $data = $res->text();

            if (!$res->ok) {
                $data = gJSON::parseable($data) ? gJSON::parse($data) : [];
                throw new Exception($data['message'] ?? 'Error en ApiRest FILES, no hay respuesta del servidor');
            }

            $data = gJSON::parse($data);

            $evidenceJpa->file = $data['data']['file'];
            $evidenceJpa->save();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. Evidencia actualizada con exito');
            $response->setData($res->json());
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

    public function delete(Request $request, string $id)
    {
        $response = new Response();
        try {
            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'evidences', 'update')) {
                throw new Exception('No tienes para listar evidencias');
            }

            if (
                !isset($id)
            ) {
                throw new Exception('El ID es nesesario para esta operación');
            }

            $evidenceJpa = Evidence::where('file', $request->id)->first();
            if (!$evidenceJpa) {
                throw new Exception("No se encontro evidencia");
            }

            $evidenceJpa->status = "0";

            $res = new gFetch("{$_ENV['FILES_URL']}/api/files/{$id}", [
                'method' => 'DELETE',
                'headers' => [
                    "Content-Type: application/json",
                    "SoDe-Auth-Token: {$_ENV['FILES_TOKEN']}",
                    "SoDe-Auth-Service: activity",
                ]
            ]);

            $data = $res->text();

            if (!$res->ok) {
                $data = gJSON::parseable($data) ? gJSON::parse($data) : [];
                throw new Exception($data['message'] ?? 'Error en ApiRest FILES, no hay respuesta del servidor');
            }

            $data = gJSON::parse($data);

            $evidenceJpa->delete();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. Evidencia eliminada correctamente');
            $response->setData($res->json());
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
