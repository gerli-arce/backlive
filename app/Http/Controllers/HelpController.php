<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function update(Request $request){
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
}
