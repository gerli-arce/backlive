<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LifeGoal;

class LifeGoalController extends Controller
{
    public function store(Request $request){
        $response = new Response();
        try {
            if(!isset($request->goal) ||
                !isset($request->_user) ||
                !isset($request->date_start) ||
                !isset($request->date_end)
            ){
                throw new Exception("Error en los datos de entrada");
            }

            $goalJpa = new LifeGoal();
            $goalJpa->goal = $request->goal;
            $goalJpa->_user = $request->_user;
            $goalJpa->date_start = $request->date_start;
            $goalJpa->date_end = $request->date_end;
            $goalJpa->status = "1";
            $goalJpa->save();

            $response->setStatus(200);
            $response->setMessage('Operación Correcta. Meta definida');
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
    public function getLifeGoalByUser(Request $request, $username){
        $response = new Response();
        try {
            $goalsJpa = LifeGoal::select([
                'lifegoals.id',
                'lifegoals.goal',
                'lifegoals.date_start',
                'lifegoals.date_end',
                'lifegoals.status'
            ])
            ->leftjoin('users','lifegoals._user','=','users.id')
            ->where('users.username','=',$username)->get();

            $response->setData($goalsJpa->toArray());
            $response->setStatus(200);
            $response->setMessage('Usuario agregado correctamente');
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