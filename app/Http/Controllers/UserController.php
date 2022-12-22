<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Response;
use App\gLibraries\guid;
use App\gLibraries\gtrace;
use App\gLibraries\gjson;
use App\gLibraries\gvalidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;


class UserController extends Controller
{

  public function index(Request $request)
  {
    $response = new Response();
    try {
      $usersJpa = User::select([
        'users.id',
        'users.username',
        'users.password',
        'users.lastname',
        'users.name',
        'users.email',
        'users.phone_prefix',
        'users.phone_number',
        'users.status',
      ])
        ->get();

      $users = array();
      foreach ($usersJpa as $userJpa) {
        $user = gJSON::restore($userJpa->toArray());
     
        unset($user['password']);
        $users[] = $user;
      }
      $response->setStatus(200);
      $response->setMessage('Operación correcta');
      $response->setData($users);
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

  public function getUser($username)
  {
    $response = new Response();
    try {
      $userJpa = User::select([

        'users.relative_id',
        'users.username',
        'users.name',
        'users.lastname',
        'users.email',
        'users.phone_number',
        'users.phone_prefix',
        'users.status',
        'roles.role AS role.role',
      ])
        ->leftjoin('roles', 'users._role', '=', 'roles.id')
        ->where('username', $username)
        ->first();

      if (!$userJpa) {
        throw new Exception('Este usuario no existe');
      }

      if (!$userJpa->status) {
        throw new Exception('Este usuario se encuentra inactivo');
      }

      $user = gJSON::restore($userJpa->toArray());

      $response->setStatus(200);
      $response->setMessage('Operación correcta');
      $response->setData($user);
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
      if (!gvalidate::check($role->permissions, 'users', 'read')) {
        throw new Exception('No tienes permisos para listar los usuarios del sistema');
      }

      $query = User::select([
        'users.id',
        'users.relative_id',
        'users.username',
        'users.password',
        'users.dni',
        'users.lastname',
        'users.name',
        'users.email',
        'users.phone_prefix',
        'users.phone_number',
        'roles.id AS role.id',
        'roles.role AS role.role',
        'roles.description AS role.description',
        'roles.permissions AS role.permissions',
        'users.status',
      ])
        ->leftjoin('roles', 'users._role', '=', 'roles.id')
        ->where('roles.priority', '>=', $role->priority)
        ->orderBy('users.' . $request->order['column'], $request->order['dir']);

      if (!$request->all) {
        $query->whereNotNull('users.status');
      }

      $query->where(function ($q) use ($request) {
        $column = $request->search['column'];
        $type = $request->search['regex'] ? 'like' : '=';
        $value = $request->search['value'];
        $value = $type == 'like' ? DB::raw("'%{$value}%'") : $value;
        if ($column == 'username' || $column == '*') {
          $q->where('users.username', $type, $value);
        }
        if ($column == 'lastname' || $column == '*') {
          $q->orWhere('users.lastname', $type, $value);
        }
        if ($column == 'name' || $column == '*') {
          $q->orWhere('users.name', $type, $value);
        }
        if ($column == 'email' || $column == '*') {
          $q->orWhere('users.email', $type, $value);
        }
        if ($column == 'phone_number' || $column == '*') {
          $q->orWhere('users.phone_number', $type, $value);
        }
        if ($column == '_role' || $column == '*') {
          $q->orWhere('roles.role', $type, $value);
        }
      });

      $iTotalDisplayRecords = $query->count();
      $usersJpa = $query
        ->skip($request->start)
        ->take($request->length)
        ->get();

      $users = array();
      foreach ($usersJpa as $userJpa) {
        $user = gJSON::restore($userJpa->toArray());
        $user['role']['permissions'] = gJSON::parse($user['role']['permissions']);
        unset($user['password']);
        $users[] = $user;
      }
      $response->setStatus(200);
      $response->setMessage('Operación correcta');
      $response->setDraw($request->draw);
      $response->setITotalDisplayRecords($iTotalDisplayRecords);
      $response->setITotalRecords(User::count());
      $response->setData($users);
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

      $userValidation = User::select(['users.username'])->where('username', $request->username)->first();

      if ($userValidation) {
        throw new Exception("Error. Este usuario ya existe");
      }

      $userJpa = new User();

      $userJpa->username = $request->username;
      $userJpa->password = password_hash($request->password, PASSWORD_DEFAULT);
      $userJpa->relative_id = guid::short();

      if($request->name){
        $userJpa->name = $request->name;
      }

      if($request->lastname){
        $userJpa->lastname = $request->lastname;
      }

      if ($request->email) {
        $userJpa->email = $request->email;
      }

      if (
        isset($request->phone_prefix) &&
        isset($request->phone_number)
        ) {
          $userJpa->phone_prefix = $request->phone_prefix;
          $userJpa->phone_number = $request->phone_number;
        }
        
      $userJpa->date_creation = gTrace::getDate('mysql');
      $userJpa->status = "1";

      $userJpa->save();
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

  public function update(Request $request)
  {
    $response = new Response();
    try {
      if (
        !isset($request->username) &&
        !isset($request->_role) &&
        !isset($request->dni) &&
        !isset($request->lastname) &&
        !isset($request->name)
      ) {
        throw new Exception("Error: No deje campos vacíos");
      }

      [$status, $message, $role] = gValidate::get($request);
      if ($status != 200) {
        throw new Exception($message);
      }
      if (!gvalidate::check($role->permissions, 'users', 'update')) {
        throw new Exception('No tienes permisos para modificar usuarios en el sistema');
      }

      $userJpa = User::find($request->id);
      if (!$userJpa) {
        throw new Exception("Este usuario no existe");
      }

      if (
        isset($request->image_type) &&
        isset($request->image_mini) &&
        isset($request->image_full)
      ) {
        if (
          $request->image_type != 'none' &&
          $request->image_mini != 'none' &&
          $request->image_full != 'none'
        ) {
          $userJpa->image_type = $request->image_type;
          $userJpa->image_mini = base64_decode($request->image_mini);
          $userJpa->image_full = base64_decode($request->image_full);
        } else {
          $userJpa->image_type = null;
          $userJpa->image_mini = null;
          $userJpa->image_full = null;
        }
      }

      $userJpa->username = $request->username;

      if (isset($request->password) && $request->password) {
        $userJpa->password = password_hash($request->password, PASSWORD_DEFAULT);
        $userJpa->auth_token = null;
      }

      $userJpa->_role = $request->_role;
      $userJpa->dni = $request->dni;
      $userJpa->lastname = $request->lastname;
      $userJpa->name = $request->name;
      if (
        isset($request->phone_prefix) &&
        isset($request->phone_number)
      ) {
        $userJpa->phone_prefix = $request->phone_prefix;
        $userJpa->phone_number = $request->phone_number;
      }
      if (isset($request->email)) {
        $userJpa->email = $request->email;
      }
      if (gValidate::check($role->permissions, 'views', 'change_status'))
        if (isset($request->status))
          $userJpa->status = $request->status;

      $userJpa->save();

      $response->setStatus(200);
      $response->setMessage('Usuario actualizado correctamente');
      $response->setData($request->toArray());
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

  public function destroy(Request $request)
  {
    $response = new Response();
    try {

      [$status, $message, $role] = gValidate::get($request);
      if ($status != 200) {
        throw new Exception($message);
      }
      if (!gValidate::check($role->permissions, 'users', 'delete_restore')) {
        throw new Exception('No tienes permisos para eliminar usuarios del sistema');
      }

      if (
        !isset($request->id)
      ) {
        throw new Exception("Error: Es necesario el ID para esta operación");
      }

      $userJpa = User::find($request->id);

      if (!$userJpa) {
        throw new Exception("Este usuario no existe");
      }

      $userJpa->status = null;
      $userJpa->save();

      $response->setStatus(200);
      $response->setMessage('El usuario se ha eliminado correctamente');
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
      if (!gValidate::check($role->permissions, 'users', 'delete_restore')) {
        throw new Exception('No tienes permisos para restaurar usuarios del sistema');
      }

      if (
        !isset($request->id)
      ) {
        throw new Exception("Error: Es necesario el ID para esta operación");
      }

      $userJpa = User::find($request->id);
      if (!$userJpa) {
        throw new Exception("Este usuario no existe");
      }
      $userJpa->status = "1";
      $userJpa->save();

      $response->setStatus(200);
      $response->setMessage('El usuario ha sido restaurado correctamente');
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

  public function searchByMedia(Request $request)
  {
    $response = new Response();
    try {
      [$status, $message, $role] = gValidate::get($request);
      if ($status != 200) {
        throw new Exception($message);
      }
      if (!gValidate::check($role->permissions, 'issues', 'share')) {
        throw new Exception('No tienes permisos para compartir informes a usuarios');
      }

      $query = User::select([
        'users.relative_id',
        'users.name',
        'users.lastname',
        'roles.role AS role.role',
      ])
        ->leftjoin('roles', 'users._role', '=', 'roles.id');

      if ($request->media == 'whatsapp') {
        $query->whereNotNull('phone_prefix');
        $query->whereNotNull('phone_number');
      } else {
        $query->whereNotNull('email');
      }

      $query->where(function ($q) use ($request) {
        $q->orWhere('users.name', 'like', DB::raw("'%{$request->term}%'"));
        $q->orWhere('users.lastname', 'like', DB::raw("'%{$request->term}%'"));
        $q->orWhere('roles.role', 'like', DB::raw("'%{$request->term}%'"));
      });

      $jpas = $query->take(5)->get();

      $client = [];
      foreach ($jpas as $jpa) {
        $client[] = gJSON::restore($jpa->toArray());
      }

      $response->setStatus(200);
      $response->setMessage('Operación correcta');
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