<?php

namespace App\Http\Controllers;

use App\gLibraries\gFetch;
use App\gLibraries\gJSON;
use App\gLibraries\gTrace;
use App\gLibraries\gValidate;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Invoice;
use App\Models\Response;
use App\Models\User;
use App\Models\ViewIssues;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{

    public function paginate(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'invoices', 'read')) {
                throw new Exception('No tienes permisos para listar informes de actividades del sistema');
            }

            $query = ViewIssues::select();

            $o_column = $request->order['column'];
            $o_dir = $request->order['dir'];
            if (!in_array($o_column, ['id', 'description', 'activities', 'invoice_number', 'invoice_date', 'month_from'])) {
                throw new Exception('No se puede ordenar por la columna ' . $o_column);
            }

            $query->orderBy($o_column, $o_dir);

            $query->where(function ($q) use ($request) {
                $column = $request->search['column'];
                $type = $request->search['regex'] ? 'like' : '=';
                $value = $request->search['value'];
                $value = $type == 'like' ? DB::raw("'%{$value}%'") : $value;
                if ($column == 'description' || $column == '*') {
                    $q->where('description', $type, $value);
                }
                if ($column == 'invoice_number' || $column == '*') {
                    $q->orWhere('invoice_number', $type, $value);
                }
                if ($column == 'invoice_date' || $column == '*') {
                    $q->orWhere('invoice_date', $type, $value);
                }
                if ($column == 'issue_date' || $column == '*') {
                    $q->orWhere('issue_date', $type, $value);
                }
            });

            $iTotalDisplayRecords = $query->count();
            $jpas = $query
                ->skip($request->start)
                ->take($request->length)
                ->get();

            $client = $jpas->toArray();

            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setDraw($request->draw);
            $response->setITotalDisplayRecords($iTotalDisplayRecords);
            $response->setITotalRecords(ViewIssues::count());
            $response->setData($client);
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

    public function create(Request $request)
    {
        $response = new Response();
        try {
            [$status, $message, $role] = gValidate::get($request);

            if ($status != 200) {
                throw new Exception($message);
            }

            if (!gValidate::check($role->permissions, 'issues', 'create')) {
                throw new Exception('No tiene permisos para crear informes de actividades');
            }

            $activities = Activity::where('status', 'ACEPTADA')
                ->whereNull('_invoice')->count();

            if ($activities == 0) {
                throw new Exception('No se puede generar el informe. No hay actividades aceptadas');
            }

            $jpa = new Invoice();
            $jpa->description = 'Informe emitido el ' . gTrace::getDate('long');
            $jpa->issue_date = gTrace::getDate('mysql');
            $jpa->save();

            $updates = Activity::whereIn('status', ['ACEPTADA', 'RECHAZADA'])
                ->whereNull('_invoice')
                ->update([
                    '_invoice' => $jpa->id
                ]);

            $response->setStatus(200);
            $response->setMessage("Se ha generado el informe con {$updates} actividades ACEPTADAS y RECHAZADAS");
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

            if (!gValidate::check($role->permissions, 'issues', 'update')) {
                throw new Exception('No tiene permisos para actualizar informes de actividades');
            }

            if (
                !isset($request->id) ||
                !isset($request->description) ||
                !isset($request->invoice_number) ||
                !isset($request->invoice_date) ||
                !isset($request->month_from) ||
                !isset($request->month_to)
            ) {
                throw new Exception('Error en los datos de entrada. No deje campos vacíos');
            }

            $jpa = Invoice::find($request->id);

            if (!$jpa) {
                throw new Exception('El informe que deseas actualizar no se encuentra disponible');
            }

            $jpa->description = $request->description;
            $jpa->invoice_number = $request->invoice_number;
            $jpa->invoice_date = $request->invoice_date;
            $jpa->month_from = $request->month_from;
            $jpa->month_to = $request->month_to;

            $jpa->save();

            $response->setStatus(200);
            $response->setMessage("Se han actualizado los datos del informe satisfactoriamente");
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

    public function savePDF(Request $request)
    {
        $response = new Response();
        try {
            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'issues', 'generate_pdf')) {
                throw new Exception('No tienes permisos para generar PDFs de informes en el sistema');
            }

            if (
                !isset($request->id) ||
                !isset($request->content)
            ) {
                throw new Exception('Error en los datos de entrada');
            }

            $jpa = Invoice::find($request->id);

            if (!$jpa) {
                throw new Exception('El informe que intentas generar no existe');
            }

            $method = 'POST';
            $body = array();
            if ($jpa->file) {
                $method = 'PATCH';
                $body['file'] = $jpa->file;
            }

            $body['type'] = 'application/pdf';
            $body['content'] = $request->content;

            $res = new gFetch("{$_ENV['FILES_URL']}/api/files", [
                'method' => $method,
                'body' => $body,
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

            $jpa->file = $data['data']['file'];
            $jpa->save();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. El informe ha sido guardado con éxito');
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

    public function share(Request $request)
    {
        $response = new Response();

        $options = array();

        try {
            [$status, $message, $role] = gValidate::get($request);

            if ($status != 200) {
                throw new Exception($message);
            }

            if (!gValidate::check($role->permissions, 'issues', 'share')) {
                throw new Exception('No tiene permisos para compatir informes de actividades');
            }

            if (
                !isset($request->id) ||
                !isset($request->media) ||
                !isset($request->users)
            ) {
                throw new Exception('Error en los datos de entrada. No deje campos vacíos');
            }

            if (!in_array($request->media, ['whatsapp', 'email'])) {
                throw new Exception('El medio de envío no existe');
            }

            $media = strtoupper($request->media);

            $issueJpa = Invoice::find($request->id);

            if (!$issueJpa) {
                throw new Exception('El informe que quieres enviar no existe');
            }

            if (!$issueJpa->file) {
                throw new Exception('El informe no tiene PDF generado. Intenta generarlo nuevamente.');
            }

            [$year_from, $month_from] = explode('-', $issueJpa->month_from);
            [$year_to, $month_to] = explode('-', $issueJpa->month_to);

            $query = User::select([
                DB::raw("CONCAT(name, ' ', lastname) AS name"),
                DB::raw("CONCAT(phone_prefix, phone_number) AS phone"),
                'email'
            ]);

            if ($request->media == 'whatsapp') {
                $query->whereNotNull('phone_prefix');
                $query->whereNotNull('phone_number');
            } else {
                $query->whereNotNull('email');
            }

            $usersJpa = $query->whereIn('relative_id', $request->users)
                ->get();


            $phones = array();
            $emails = array();
            foreach ($usersJpa as $userJpa) {
                if (!isset($phones[$userJpa->phone])) {
                    $phones[] = [
                        'name' => $userJpa->name,
                        'phone' => $userJpa->phone
                    ];
                }
                if (!isset($emails[$userJpa->email])) {
                    $emails[] = [
                        'name' => $userJpa->name,
                        'email' => $userJpa->email
                    ];
                }
            }

            $body = array();
            $body['phones'] = $phones;
            $body['emails'] = $emails;
            $body['components'] = array();

            $body['components']['urlpdf'] = "{$_ENV['FILES_URL']}/api/files/{$issueJpa->file}/{$_ENV['FILES_SERVICE']}/{$_ENV['FILES_TOKEN']}";
            $body['components']['namepdf'] = 'Informe.pdf';
            $body['components']['startdate'] = gTrace::month($month_from) . ' ' . $year_from;
            $body['components']['enddate'] = gTrace::month($month_to) . ' ' . $year_to;
            $body['components']['issuedate'] = gTrace::getDate('long');

            $token = $_ENV["{$media}_TOKEN"];
            $service = $_ENV["{$media}_SERVICE"];

            $url = $_ENV["{$media}_URL"] . '/api/send/activity/issue';

            $options = [
                'url' => $url,
                'method' => 'POST',
                'body' => $body,
                'headers' => [
                    "Content-Type: application/json",
                    "SoDe-Auth-Token: {$token}",
                    "SoDe-Auth-Service: {$service}",
                ]
            ];

            $res = new gFetch($_ENV["{$media}_URL"] . '/api/send/activity/issue', $options);

            $data = $res->text();

            if (!$res->ok) {
                $data = gJSON::parseable($data) ? gJSON::parse($data) : [];
                throw new Exception($data['message'] ?? "Error en ApiRest {$media}, no hay respuesta del servidor");
            }

            $response->setStatus(200);
            $response->setMessage("El informe ha sido compartido correctamente");
        } catch (\Throwable $th) {
            $response->setStatus(400);
            $response->setMessage($th->getMessage());
            $response->setData($options);
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }
}
