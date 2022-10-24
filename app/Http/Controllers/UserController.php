<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Response;
use App\Models\User;
use Exception;
class UserController extends Controller
{
    public function get(Request $request){

        $response = new Response();
        try {
            $rowUser = User::all();
            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setData($rowUser->toArray());
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

    public function register(Request $request){
        $response = new Response();
        try {

            if(
                !isset($request->username) ||
                !isset($request->password) ||
                !isset($request->name) ||
                !isset($request->lastname) ||
                !isset($request->email) ||
                !isset($request->date_creation)
            ){
                throw new Exception('Datos incompletos para reguistro');
            }

            $userValidation = User::select(['users.username'])->where('username', $request->username)->first();

            if($userValidation){
                throw new Exception('El usuario ya existe');
            }

            $rowUser = new User();
            $rowUser -> username = $request->username;
            $rowUser -> password = password_hash($request->password, PASSWORD_DEFAULT);
            $rowUser -> name = $request->name;
            $rowUser -> lastname = $request->lastname;
            $rowUser -> email = $request->email;
            $rowUser -> phone_number = $request->phone_number;
            $rowUser -> phone_prefix = $request->phone_prefix;
            $rowUser -> image_full = $request->image_full;
            $rowUser -> image_mini = $request->image_mini;
            $rowUser -> image_type = $request->image_type;
            $rowUser -> date_creation = $request->date_creation;
            $rowUser ->save();

            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setData($rowUser->toArray());
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

    public function login(Request $request){
        
    }
    
}
