<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\gLibraries\gjson;
use App\gLibraries\gtrace;
use App\gLibraries\gvalidate;
use App\Models\Response;
use Illuminate\Support\Facades\DB;
use App\Models\Activity;
use App\Models\View_activities;
use Exception;

class ActivityController extends Controller
{
    public function reject(Request $request, string $id)
    {
        $response = new Response();
        try {
            [$status, $message, $role, $userid] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'activities/pending', 'update')) {
                throw new Exception('No tienes permisos para eliminar las actividades del sistema');
            }

            if (!$id) {
                throw new Exception('Se debe enviar un id de actividad');
            }

            $activityJpa = Activity::find($id);
            $activityJpa->status = "RECHAZADA";
            $activityJpa->_date_status = gTrace::getDate('mysql');
            $activityJpa->_date_update = gTrace::getDate('mysql');
            $activityJpa->update_description = 'Se ha modificado el estado de PENDIENTE a RECHAZADA';
            $activityJpa->save();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. La actividad se a eliminado');
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

    public function paginatePending(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'activities/pending', 'read')) {
                throw new Exception('No tienes permisos para listar las actividades pendientes del sistema');
            }

            $query = View_activities::select([
                'id',
                'environment__id',
                'environment__environment',
                'environment__description',
                'module__id',
                'module__module',
                'module__service__id',
                'module__service__correlative',
                'module__service__repository',
                'module__service__service',
                'activity',
                'complexity',
                'priority',
                'hours__relative',
                'evidences',
                'user__creation__relative_id',
                'user__creation__lastname',
                'user__creation__name',
                'user__solved__relative_id',
                'user__solved__lastname',
                'user__solved__name',
                'date__creation',
                'date__update',
                'status'
            ])
                ->whereIn('status', ['PENDIENTE', 'EN CURSO']);

            $o_column = $request->order['column'] ?? 'smart';
            $o_dir = $request->order['dir'] ?? 'asc';
            switch ($o_column) {
                case 'date':
                    $query->orderBy('date__update', $o_dir);
                    break;
                case 'priority':
                    $query->orderBy('priority_order', $o_dir);
                    break;
                default:
                    $query->orderBy('status', $o_dir);
                    $query->orderBy('priority_order', $o_dir);
                    $query->orderBy('date__update', $o_dir);
                    break;
            }

            $query->where(function ($q) use ($request) {
                $search = $request->search ?? [];
                $s_value = $search['value'] ?? '';
                $q->orWhereRaw("module__module LIKE CONCAT('%', ?, '%')", [$s_value]);

                $q->orWhereRaw("module__service__service LIKE CONCAT('%', ?, '%')", [$s_value]);

                $q->orWhereRaw("activity LIKE CONCAT('%', ?, '%')", [$s_value]);
            });

            $iTotalDisplayRecords = $query->count();
            $iTotalRecords = View_activities::whereIn('status', ['PENDIENTE', 'EN CURSO'])
                ->count();
            $activitiesJpa = $query
                ->skip($request->start)
                ->take($request->length)
                ->get();

            $activities = array();

            foreach ($activitiesJpa as $activityJpa) {
                $activity = gJSON::restore($activityJpa->toArray(), '__');
                $activities[] = $activity;
            }
            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setITotalDisplayRecords($iTotalDisplayRecords);
            $response->setITotalRecords($iTotalRecords);
            $response->setData($activities);
        } catch (\Throwable $th) {
            $response->setStatus(400);
            $response->setMessage($th->getMessage() . ' ln' . $th->getLine());
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }

    public function paginateDone(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'activities/pending', 'read')) {
                throw new Exception('No tienes permisos para listar las actividades pendientes del sistema');
            }

            $query = View_activities::select([
                'id',
                'environment__id',
                'environment__environment',
                'environment__description',
                'module__id',
                'module__module',
                'module__service__id',
                'module__service__correlative',
                'module__service__repository',
                'module__service__service',
                'activity',
                'complexity',
                'priority',
                'hours__relative',
                'evidences',
                'user__creation__relative_id',
                'user__creation__lastname',
                'user__creation__name',
                'user__solved__relative_id',
                'user__solved__lastname',
                'user__solved__name',
                'user__update__relative_id',
                'user__update__lastname',
                'user__update__name',
                'date__creation',
                'date__update',
                'date__status',
                'update_description',
                'status'
            ])
                ->where('status', 'REALIZADO');

            $o_column = $request->order['column'] ?? 'smart';
            $o_dir = $request->order['dir'] ?? 'asc';
            switch ($o_column) {
                case 'date':
                    $query->orderBy('date__update', $o_dir);
                    break;
                case 'priority':
                    $query->orderBy('priority_order', $o_dir);
                    break;
                default:
                    $query->orderBy('status', $o_dir);
                    $query->orderBy('priority_order', $o_dir);
                    $query->orderBy('date__update', $o_dir);
                    break;
            }

            $query->where(function ($q) use ($request) {
                $search = $request->search ?? [];
                $s_value = $search['value'] ?? '';
                $q->orWhereRaw("module__module LIKE CONCAT('%', ?, '%')", [$s_value]);

                $q->orWhereRaw("module__service__service LIKE CONCAT('%', ?, '%')", [$s_value]);

                $q->orWhereRaw("activity LIKE CONCAT('%', ?, '%')", [$s_value]);
            });

            $iTotalDisplayRecords = $query->count();
            $iTotalRecords = View_activities::where('status', 'REALIZADO')
                ->count();
            $activitiesJpa = $query
                ->skip($request->start)
                ->take($request->length)
                ->get();

            $activities = array();

            foreach ($activitiesJpa as $activityJpa) {
                $activity = gJSON::restore($activityJpa->toArray(), '__');
                $activities[] = $activity;
            }
            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setITotalDisplayRecords($iTotalDisplayRecords);
            $response->setITotalRecords($iTotalRecords);
            $response->setData($activities);
        } catch (\Throwable $th) {
            $response->setStatus(400);
            $response->setMessage($th->getMessage() . ' ln' . $th->getLine());
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }

    public function paginateToReView(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'activities/toreview', 'read')) {
                throw new Exception('No tienes permisos para listar las actividades pedientes del sistema');
            }

            $query = View_activities::select([
                'id',
                'module__id',
                'module__module',
                'module__service__id',
                'module__service__correlative',
                'module__service__repository',
                'module__service__service',
                'activity',
                'complexity',
                'priority',
                'observation',
                'hours__relative',
                'hours__accepted',
                'evidences',
                'user__creation__relative_id',
                'user__creation__lastname',
                'user__creation__name',
                'user__solved__relative_id',
                'user__solved__lastname',
                'user__solved__name',
                'user__update__relative_id',
                'user__update__lastname',
                'user__update__name',
                'user__observation__relative_id',
                'user__observation__lastname',
                'user__observation__name',
                'date__creation',
                'date__update',
                'date__status',
                'date__observation',
                'update_description',
                'status'
            ])
                ->where('view_activities.status', 'PARA REVISAR');

            $query->orderBy($request->order['column'], $request->order['dir']);

            $query->where(function ($q) use ($request) {
                $column = $request->search['column'];
                $type = $request->search['regex'] ? 'like' : '=';
                $value = $request->search['value'];
                $value = $type == 'like' ? DB::raw("'%{$value}%'") : $value;
                if ($column == 'module' || $column == '*') {
                    $q->where('module__module', $type, $value);
                }
                if ($column == 'activity' || $column == '*') {
                    $q->orWhere('activity', $type, $value);
                }
                if ($column == 'evidences' || $column == '*') {
                    $q->orWhere('evidences', $type, $value);
                }
                if ($column == 'hours_relative' || $column == '*') {
                    $q->orWhere('hours__relative', $type, $value);
                }
                if ($column == 'hours_accepted' || $column == '*') {
                    $q->orWhere('hours__accepted', $type, $value);
                }
                if ($column == 'observation' || $column == '*') {
                    $q->orWhere('observation', $type, $value);
                }
                if ($column == 'status' || $column == '*') {
                    $q->orWhere('status', $type, $value);
                }
            });

            $iTotalDisplayRecords = $query->count();
            $iTotalRecords = View_activities::where('view_activities.status', 'PARA REVISAR')
                ->count();
            $activitiesJpa = $query
                ->skip($request->start)
                ->take($request->length)
                ->get();

            $activities = array();

            foreach ($activitiesJpa as $activityJpa) {
                $activity = gJSON::restore($activityJpa->toArray(), '__');
                $activities[] = $activity;
            }


            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setDraw($request->draw);
            $response->setITotalDisplayRecords($iTotalDisplayRecords);
            $response->setITotalRecords(View_activities::where('view_activities.status', 'PARA REVISAR')->count());
            $response->setData($activities);
        } catch (\Throwable $th) {
            $response->setStatus(400);
            $response->setMessage($th->getMessage() . ' ln' . $th->getLine());
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }

    public function paginateReviewed(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'activities/reviewed', 'read')) {
                throw new Exception('No tienes permisos para listar las actividades revisadas del sistema');
            }

            $query = View_activities::select()
                ->whereIn('status', ['ACEPTADA', 'RECHAZADA'])
                ->whereNull('invoice__id');

            $o_column = $request->order['column'] ?? '_module';
            $o_dir = $request->order['dir'] ?? 'asc';

            switch ($o_column) {
                case '_module':
                    $query->orderBy('module__module', $o_dir);
                    break;
                case 'activity':
                    $query->orderBy('activity', $o_dir);
                    break;
                case 'relative_hours':
                    $query->orderBy('hours__relative', $o_dir);
                    break;
                case 'observation':
                    $query->orderBy('observation', $o_dir);
                    break;
                case 'accepted_hours':
                    $query->orderBy('hours__accepted', $o_dir);
                    break;
                case 'status':
                    $query->orderBy('status', $o_dir);
                    break;
                default:
                    $query->orderBy('id', $o_dir);
                    break;
            }

            $query->where(function ($q) use ($request) {
                $column = $request->search['column'];
                $type = $request->search['regex'] ? 'like' : '=';
                $value = $request->search['value'];
                $value = $type == 'like' ? DB::raw("'%{$value}%'") : $value;

                if ($column == '_module' || $column == '*') {
                    $q->where('module__module', $type, $value);
                }
                if ($column == '_service' || $column == '*') {
                    $q->orWhere('module__service__service', $type, $value);
                }
                if ($column == 'activity' || $column == '*') {
                    $q->orWhere('activity', $type, $value);
                }
            });

            $iTotalDisplayRecords = $query->count();
            $jpas = $query
                ->skip($request->start)
                ->take($request->length)
                ->get();

            $client = array();
            foreach ($jpas as $jpa) {
                $client[] = gJSON::restore($jpa->toArray(), '__');
            }

            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setDraw($request->draw);
            $response->setITotalDisplayRecords($iTotalDisplayRecords);
            $response->setITotalRecords(View_activities::whereIn('status', ['ACEPTADA', 'RECHAZADA'])->count());
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

    public function paginateDeleted(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'activities/deleted', 'read')) {
                throw new Exception('No tienes permisos para listar las actividades eliminadas del sistema');
            }

            $query = View_activities::select()
                ->where('status', 'ELIMINADA');

            $o_column = $request->order['column'] ?? '_module';
            $o_dir = $request->order['dir'] ?? 'asc';

            switch ($o_column) {
                case '_module':
                    $query->orderBy('module__module', $o_dir);
                    break;
                case '_service':
                    $query->orderBy('module__service__service', $o_dir);
                    break;
                case 'activity':
                    $query->orderBy('activity', $o_dir);
                    break;
                default:
                    $query->orderBy('id', $o_dir);
                    break;
            }

            $query->where(function ($q) use ($request) {
                $column = $request->search['column'];
                $type = $request->search['regex'] ? 'like' : '=';
                $value = $request->search['value'];
                $value = $type == 'like' ? DB::raw("'%{$value}%'") : $value;

                if ($column == '_module' || $column == '*') {
                    $q->where('module__module', $type, $value);
                }
                if ($column == '_service' || $column == '*') {
                    $q->orWhere('module__service__service', $type, $value);
                }
                if ($column == 'activity' || $column == '*') {
                    $q->orWhere('activity', $type, $value);
                }
            });

            $iTotalDisplayRecords = $query->count();
            $jpas = $query
                ->skip($request->start)
                ->take($request->length)
                ->get();

            $client = array();
            foreach ($jpas as $jpa) {
                $client[] = gJSON::restore($jpa->toArray(), '__');
            }

            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setDraw($request->draw);
            $response->setITotalDisplayRecords($iTotalDisplayRecords);
            $response->setITotalRecords(View_activities::where('status', 'ELIMINADA')->count());
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

    public function store(Request $request)
    {
        $response = new Response();

        try {
            [$status, $message, $role, $userid] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'activities', 'create')) {
                throw new Exception('No tienes permisos para agregar actividades en el sistema');
            }

            if (
                !isset($request->_module) ||
                !isset($request->activity) ||
                !isset($request->priority)

            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            $jpa = new Activity();
            $jpa->_environment = null;
            $jpa->_module = $request->_module;
            $jpa->activity = $request->activity;
            $jpa->complexity = null;
            $jpa->priority = $request->priority;
            $jpa->status = 'PENDIENTE';
            $jpa->relative_hours = 0;
            $jpa->observation = null;
            $jpa->accepted_hours = 0;
            $jpa->_invoice  = null;
            $jpa->update_description = 'Se asignó una nueva actividad a la lista de pendientes';

            $jpa->_user_solved = null;
            $jpa->_user_creation = $userid;
            $jpa->_user_observation = null;
            $jpa->_user_update = $userid;

            $jpa->date_observation = null;
            $jpa->date_creation = gTrace::getDate('mysql');
            $jpa->date_update = gTrace::getDate('mysql');
            $jpa->date_status = gTrace::getDate('mysql');
            $jpa->date_issue = null;

            $jpa->save();

            $response->setStatus(200);
            $response->setMessage("La actividad ha sido agregada correctamente");
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
                !isset($request->id)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            [$status, $message, $role, $userid] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'activities/pending', 'update')) {
                throw new Exception('No tienes permisos para actualizar actividades en el sistema');
            }

            $jpa = Activity::find($request->id);

            if (!$jpa) {
                throw new Exception("Error: La actividad no existe");
            }

            if ($request->_environment) {
                $jpa->_environment = $request->_environment;
            }

            if ($request->_module) {
                $jpa->_module = $request->_module;
            }

            if ($request->activity) {
                $jpa->activity = $request->activity;
            }

            if ($request->complexity) {
                $jpa->complexity = $request->complexity;
            }

            if ($request->priority) {
                $jpa->priority = $request->priority;
            }

            if ($request->status) {
                $jpa->status = $request->status;
            }

            if ($request->relative_hours) {
                $jpa->relative_hours = $request->relative_hours;
            }

            if ($request->observation) {
                $jpa->observation = $request->observation;
                $jpa->_user_observation = $userid;
                $jpa->date_observation = gTrace::getDate('mysql');
            }

            if ($request->accepted_hours) {
                $jpa->accepted_hours = $request->accepted_hours;
            }

            if ($request->_invoice) {
                $jpa->_invoice = $request->_invoice;
            }

            $jpa->_user_update = $userid;
            $jpa->date_update = gTrace::getDate('mysql');


            $jpa->update_description = 'Se actualizó datos de la actividad';

            $jpa->save();

            $response->setStatus(200);
            $response->setMessage("Actividad actualizada correctamente");
        } catch (\Throwable $th) {
            $response->setStatus(400);
            $response->setMessage($th->getMessage() . $th->getLine());
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }

    public function resolve(Request $request, string $id)
    {
        $response = new Response();
        try {
            [$status, $message, $role, $userid] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'activities/pending', 'resolve')) {
                throw new Exception('No tiene permisos para resolver actividades del sistema');
            }
            if ($id == null) {
                throw new Exception('Se debe enviar un id de actividad');
            }

            $activityJpa = Activity::find($id);

            if (!$activityJpa) {
                throw new Exception('La actividad que quieres resolver no existe');
            }

            if ($activityJpa->status != 'PENDIENTE') {
                throw new Exception('La actividad no puede ser tomada');
            }

            $activityJpa->status = 'EN CURSO';
            $activityJpa->_user_solved = $userid;
            $activityJpa->_user_update = $userid;
            $activityJpa->date_status = gTrace::getDate('mysql');
            $activityJpa->date_update = gTrace::getDate('mysql');
            $activityJpa->update_description = 'Se ha modificado el estado de PENDIENTE a EN CURSO';

            $activityJpa->save();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. La actividad está en curso y bajo su nombre');
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

    public function details(Request $request)
    {
        $response = new Response();
        try {
            [$status, $message, $role, $userid] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'activities/done', 'update')) {
                throw new Exception('No tiene permisos para resolver actividades del sistema');
            }
            if (
                !isset($request->id) ||
                !isset($request->relative_hours) ||
                !isset($request->complexity) ||
                !isset($request->_environment)
            ) {
                throw new Exception('Complete todos los campos a actualizar');
            }

            $activityJpa = Activity::find($request->id);

            if (!$activityJpa) {
                throw new Exception('La actividad que quieres actualizar no existe');
            }

            if (!in_array($activityJpa->status, ['PENDIENTE', 'EN CURSO', 'REALIZADO'])) {
                throw new Exception('La actividad no puede ser tomada');
            }

            if ($activityJpa->status != 'REALIZADO') {
                $activityJpa->status = 'EN CURSO';
                $activityJpa->_user_solved = $userid;
            }

            $activityJpa->relative_hours = $request->relative_hours;
            $activityJpa->accepted_hours = $request->relative_hours;
            $activityJpa->complexity = $request->complexity;
            $activityJpa->_environment  = $request->_environment;
            $activityJpa->_user_update = $userid;
            $activityJpa->date_status = gTrace::getDate('mysql');
            $activityJpa->date_update = gTrace::getDate('mysql');

            $activityJpa->update_description = 'Se ha modificado los detalles de la actividad';

            $activityJpa->save();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. Se actualizó los detalles de la actividad');
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

    public function markasdone(Request $request)
    {
        $response = new Response();
        try {
            [$status, $message, $role, $userid] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'activities/pending', 'markasdone')) {
                throw new Exception('No tiene permisos para marcar esta actividad realizada');
            }
            if (
                !isset($request->id)
            ) {
                throw new Exception('Envía el ID de la actividad a actualizar');
            }

            $activityJpa = Activity::find($request->id);

            if (!$activityJpa) {
                throw new Exception('La actividad que quieres actualizar no existe');
            }

            if (!in_array($activityJpa->status, ['PENDIENTE', 'EN CURSO'])) {
                throw new Exception('La actividad no puede ser marcada como realizada');
            }

            if (
                $activityJpa->relative_hours < 0 ||
                empty($activityJpa->complexity) ||
                empty($activityJpa->_environment)
            ) {
                throw new Exception('Faltan datos para que esta actividad pueda ser marcada como realizada');
            }

            $activityJpa->status = 'REALIZADO';
            $activityJpa->_user_solved = $userid;
            $activityJpa->_user_update = $userid;
            $activityJpa->date_status = gTrace::getDate('mysql');
            $activityJpa->date_update = gTrace::getDate('mysql');

            $activityJpa->update_description = 'Se cambió la actividad a estado REALIZADO';

            $activityJpa->save();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. Se movió la actividad a realizados');
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

    public function markastoreview(Request $request)
    {
        $response = new Response();
        try {
            [$status, $message, $role, $userid] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'activities/pending', 'markastoreview')) {
                throw new Exception('No tiene permisos para mandar a revisar esta actividad');
            }
            if (
                !isset($request->id)
            ) {
                throw new Exception('Envía el ID de la actividad a actualizar');
            }

            $activityJpa = Activity::find($request->id);

            if (!$activityJpa) {
                throw new Exception('La actividad que quieres actualizar no existe');
            }

            if ($activityJpa->status != 'REALIZADO') {
                throw new Exception('La actividad no puede ser mandada a revisar porque aún no ha sido marcado como realizado');
            }

            if (
                $activityJpa->relative_hours < 0 ||
                empty($activityJpa->complexity) ||
                empty($activityJpa->_environment)
            ) {
                throw new Exception('Faltan datos para que esta actividad pueda ser mandada a revisar');
            }

            $activityJpa->status = 'PARA REVISAR';
            $activityJpa->_user_solved = $userid;
            $activityJpa->_user_update = $userid;
            $activityJpa->date_status = gTrace::getDate('mysql');
            $activityJpa->date_update = gTrace::getDate('mysql');

            $activityJpa->update_description = 'Se cambió la actividad a estado PARA REVISAR';

            $activityJpa->save();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. Se movió la actividad a "Para revisar"');
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

    public function cancel(Request $request, string $id)
    {
        $response = new Response();
        try {
            [$status, $message, $role, $userid] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'activities/pending', 'resolve')) {
                throw new Exception('No tiene permisos para resolver y rechazar actividades del sistema');
            }
            if ($id == null) {
                throw new Exception('Se debe enviar un id de actividad');
            }

            $activityJpa = Activity::find($id);

            if (!$activityJpa) {
                throw new Exception('La actividad que quieres resolver no existe');
            }

            if (!in_array($activityJpa->status, ['EN CURSO', 'REALIZADO'])) {
                throw new Exception('La actividad no puede ser cancelada');
            }

            $activityJpa->status = 'PENDIENTE';
            $activityJpa->_user_solved = null;
            $activityJpa->_user_update = $userid;
            $activityJpa->date_status = gTrace::getDate('mysql');
            $activityJpa->date_update = gTrace::getDate('mysql');
            $activityJpa->update_description = 'Se ha modificado el estado a PENDIENTE';

            $activityJpa->save();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. La actividad está en estado pendiente nuevamente');
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

    public function observe(Request $request, string $action)
    {
        abort_if(!in_array($action, ['observe', 'accept', 'decline']), 404);

        $response = new Response();
        try {
            [$status, $message, $role, $userid] = gValidate::get($request);

            if ($status != 200) {
                throw new Exception($message);
            }

            if (!gvalidate::check($role->permissions, 'activities/toreview', $action)) {
                throw new Exception('No tienes permisos para realizar observaciones sobre actividades en el sistema');
            }

            if (
                !isset($request->id) ||
                !isset($request->accepted_hours)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            $jpa = Activity::find($request->id);

            if (!$jpa) {
                throw new Exception("Error: La actividad que deseas observar no existe");
            }

            $actions = [
                'observe' => null,
                'accept' => 'ACEPTADA',
                'decline' => 'RECHAZADA'
            ];

            if ($jpa->status != "PARA REVISAR") {
                throw new Exception("Error: La actividad debe de tener el estado PARA REVISAR para poder ser OBSERVADA");
            }

            if ($actions[$action]) $jpa->status = $actions[$action];

            if ($request->observation) {
                $jpa->observation = $request->observation;
            }

            $jpa->accepted_hours = $request->accepted_hours;
            $jpa->_user_update = $userid;
            $jpa->_user_observation = $userid;
            $jpa->date_observation = gTrace::getDate('mysql');
            $jpa->date_update = gTrace::getDate('mysql');
            $jpa->update_description = 'Se cambio el estado de la actividad de PARA REVISAR a ' . ($actions[$action] ?? $jpa->status);

            $jpa->save();

            $response->setStatus(200);
            $response->setMessage("Actividad a sido observada correctamente");
        } catch (\Throwable $th) {
            $response->setStatus(400);
            $response->setMessage($th->getMessage() . $th->getLine());
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
            [$status, $message, $role, $userid] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'activities/toreview', 'delete')) {
                throw new Exception('No tiene permisos para elimnar o cancelar actividades del sistema');
            }
            if ($request->id == null) {
                throw new Exception('Se debe enviar un id de actividad');
            }

            $activityJpa = Activity::find($request->id);

            if (!$activityJpa) {
                throw new Exception('La actividad que quieres resolver no existe');
            }

            if ($activityJpa->status == 'FACTURADO') {
                throw new Exception('La actividad ya ha sido facturada');
            }

            $activityJpa->update_description = "Se ha modificado el estado de {$activityJpa->status} a ELIMINADA";
            $activityJpa->status = 'ELIMINADA';
            $activityJpa->_user_solved = null;
            $activityJpa->_user_update = $userid;
            $activityJpa->date_status = gTrace::getDate('mysql');
            $activityJpa->date_update = gTrace::getDate('mysql');

            $activityJpa->save();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. La actividad está en estado pendiente nuevamente');
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
            [$status, $message, $role, $userid] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'activities/deleted', 'restore')) {
                throw new Exception('No tiene permisos para restaurar esta actividad');
            }
            if (
                !isset($request->id)
            ) {
                throw new Exception('Envía el ID de la actividad a restaurar');
            }

            $jpa = Activity::find($request->id);

            if (!$jpa) {
                throw new Exception('La actividad que quieres restaurar no existe');
            }

            if ($jpa->status != 'ELIMINADA') {
                throw new Exception('La actividad no puede ser restaurada porque no tiene el estado de eliminado');
            }

            $jpa->status = 'PENDIENTE';
            $jpa->_environment = null;
            $jpa->_user_solved = null;
            $jpa->_user_update = $userid;
            $jpa->date_status = gTrace::getDate('mysql');
            $jpa->date_update = gTrace::getDate('mysql');
            $jpa->update_description = 'Se ha cambiado el estado de la actividad de ELIMINADA a PENDIENTE';

            $jpa->save();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. Se movió la actividad a pendientes');
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

    public function movetopending(Request $request)
    {
        $response = new Response();
        try {
            [$status, $message, $role, $userid] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'activities/reviewed', 'movetopending')) {
                throw new Exception('No tiene permisos para mover esta actividad a pendientes');
            }
            if (
                !isset($request->id)
            ) {
                throw new Exception('Envía el ID de la actividad a trasladar');
            }

            $jpa = Activity::find($request->id);

            if (!$jpa) {
                throw new Exception('La actividad que quieres mover no existe');
            }

            if ($jpa->status != 'RECHAZADA') {
                throw new Exception('La actividad no puede ser movida a pendientes porque no tiene el estado de RECHAZADA');
            }

            $jpa->status = 'PENDIENTE';
            $jpa->_user_update = $userid;
            $jpa->date_status = gTrace::getDate('mysql');
            $jpa->date_update = gTrace::getDate('mysql');
            $jpa->update_description = 'Se ha cambiado el estado de la actividad de RECHAZADA a PENDIENTE';

            $jpa->save();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. Se movió la actividad a pendientes');
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

    public function movetodone(Request $request)
    {
        $response = new Response();
        try {
            [$status, $message, $role, $userid] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'activities/reviewed', 'movetodone')) {
                throw new Exception('No tiene permisos para mover esta actividad a realizados');
            }
            if (
                !isset($request->id)
            ) {
                throw new Exception('Envía el ID de la actividad a trasladar');
            }

            $jpa = Activity::find($request->id);

            if (!$jpa) {
                throw new Exception('La actividad que quieres mover no existe');
            }

            if (!in_array($jpa->status, ['ACEPTADA', 'RECHAZADA'])) {
                throw new Exception('La actividad no puede ser movida a pendientes porque no tiene el estado de ACEPTADA o RECHAZADA');
            }

            $jpa->update_description = "Se ha cambiado el estado de la actividad de {$jpa->status} a REALIZADO";
            $jpa->status = 'REALIZADO';
            $jpa->_user_update = $userid;
            $jpa->date_status = gTrace::getDate('mysql');
            $jpa->date_update = gTrace::getDate('mysql');
            $jpa->save();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. Se movió la actividad a realizados');
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

    public function getByUser(Request $request, $username){
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'activities/user', 'read')) {
                throw new Exception('No tienes permisos para listar las actividades bajo su cargo');
            }

            $query = View_activities::select([
                'id',
                'environment__id',
                'environment__environment',
                'environment__description',
                'module__id',
                'module__module',
                'module__service__id',
                'module__service__correlative',
                'module__service__repository',
                'module__service__service',
                'activity',
                'complexity',
                'priority',
                'hours__relative',
                'evidences',
                'user__creation__relative_id',
                'user__creation__lastname',
                'user__creation__name',
                'user__solved__relative_id',
                'user__solved__username',
                'user__solved__lastname',
                'user__solved__name',
                'date__creation',
                'date__update',
                'date__status',
                'status'
            ])
            ->where('user__solved__username', $username);

            $o_column = $request->order['column'] ?? 'smart';
            $o_dir = $request->order['dir'] ?? 'asc';
            switch ($o_column) {
                case 'date':
                    $query->orderBy('date__update', $o_dir);
                    break;
                case 'priority':
                    $query->orderBy('priority_order', $o_dir);
                    break;
                default:
                    $query->orderBy('status', $o_dir);
                    $query->orderBy('priority_order', $o_dir);
                    $query->orderBy('date__update', $o_dir);
                    break;
            }

            if(isset($request->search['date_init']) && !isset($request->search['date_end'])){
                $query->where('date__status', '>=', $request->search['date_init']);
            }

            if(isset($request->search['date_init']) && isset($request->search['date_end'])){
                $query->where('date__status', '>=', $request->search['date_init'])
                ->where('date__status', '<=' ,$request->search['date_end']);
            }

            $query->where(function ($q) use ($request) {
                $search = $request->search ?? [];
                $s_value = $search['value'] ?? '';
                $q->orWhereRaw("module__module LIKE CONCAT('%', ?, '%')", [$s_value]);

                $q->orWhereRaw("module__service__service LIKE CONCAT('%', ?, '%')", [$s_value]);

                $q->orWhereRaw("activity LIKE CONCAT('%', ?, '%')", [$s_value]);
            });

            $iTotalDisplayRecords = $query->count();
            $iTotalRecords = View_activities::where('user__solved__username', $username)->count();
            $activitiesJpa = $query
                ->skip($request->start)
                ->take($request->length)
                ->get();

            $activities = array();

            foreach ($activitiesJpa as $activityJpa) {
                $activity = gJSON::restore($activityJpa->toArray(), '__');
                $activities[] = $activity;
            }
            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setITotalDisplayRecords($iTotalDisplayRecords);
            $response->setITotalRecords($iTotalRecords);
            $response->setData($activities);
        } catch (\Throwable $th) {
            $response->setStatus(400);
            $response->setMessage($th->getMessage() . ' ln' . $th->getLine());
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }
}
