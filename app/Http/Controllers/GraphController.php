<?php

namespace App\Http\Controllers;

use App\gLibraries\gJSON;
use App\gLibraries\gvalidate;
use App\Models\Activity;
use App\Models\Response;
use App\Models\View_activities;
use App\Models\ViewIssues;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GraphController extends Controller
{

    public function counts(Request $request)
    {
        $response = new Response();
        try {
            [$status, $message] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }

            $countsJpa = Activity::select([
                'status',
                DB::raw('COUNT(id) AS quantity')
            ])
                ->groupBy('status')
                ->whereNull('_invoice')
                ->get();

            $counts = $countsJpa->toArray();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. Se obtuvo el conteo de estados');
            $response->setData($counts);
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

    public function invoices(Request $request)
    {
        $response = new Response();
        try {
            [$status, $message] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }

            $graph = ViewIssues::select()
                ->take(6)
                ->get();

            $response->setStatus(200);
            $response->setMessage('Operación correcta. Se obtuvo los datos estadísticos de actividades realizadas');
            $response->setData($graph->toArray());
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

    public function solution(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }

            $jpas = Activity::select([
                'users.username AS user__username',
                'users.relative_id AS user__relative_id',
                'users.name AS user__name',
                'users.lastname AS user__lastname',
                'roles.role AS user__role__role',
                DB::raw('COUNT(activities.id) AS activities'),
                DB::raw('SUM(activities.relative_hours) AS hours'),
            ])
                ->join('users', 'activities._user_solved', 'users.id')
                ->join('roles', 'users._role', 'roles.id')
                ->groupBy('users.relative_id')
                ->having('users.relative_id', '<>', '')
                ->orderBy('activities.relative_hours', 'desc')
                ->take(5)
                ->get();

            $client = [];
            foreach ($jpas as $jpa) {
                $client[] = gJSON::restore($jpa->toArray(), '__');
            }

            $response->setStatus(200);
            $response->setMessage('Operación correcta. Se obtuvo los datos estadísticos de actividades realizadas');
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
}
