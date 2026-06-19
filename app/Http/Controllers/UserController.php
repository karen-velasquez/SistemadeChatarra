<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Parametro;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Requests\UserRequest;
use App\Http\Requests\changePasswordRequest;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use YoHang88\LetterAvatar\LetterAvatar;
use RealRashid\SweetAlert\Facades\Alert;

class UserController extends Controller
{
    use \App\Http\Controllers\Concerns\PrevenirRegistroDoble;

    public function __construct()
    {
        $this->middleware('auth');
    }
    public function index()
    {
        $users=User::with(['roles','ubicacion','empleado.cargo'])->where('id','<>',1)->get();
        return view('users.index',compact('users'));
    }

   public function create()
    {
        $roles = Role::where('id','>',1)->get();
        $empleados = Empleado::whereNull('deleted_at')
            ->orderBy('apellido_paterno')
            ->orderBy('nombre')
            ->get();
        $user = new User();
        $idempotencyToken = $this->generarToken('user_store_token');
        return view('users.create',compact('roles','user','empleados','idempotencyToken'));
    }

    public function datos_empleado(Request $request){
        $empleado=Empleado::find($request->id);
        return response()->json($empleado);
    }

   public function store(UserRequest $request)
    {
        if (!$this->tokenValido('user_store_token', $request->input('_idempotency_token'))) {
            Alert::error('Solicitud duplicada', 'Este registro ya fue procesado. Recargue la página para registrar uno nuevo.');
            return redirect()->route('users.index');
        }

        $user=new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->empleado_id = $request->empleado_id;
        $user->save();
        $avatar = new LetterAvatar($request->name,'circle', 64);
        $avatarDir = public_path('storage/avatares');
        if (!file_exists($avatarDir)) {
            mkdir($avatarDir, 0755, true);
        }
        $path = $avatarDir . '/' . $user->id . '.jpg';
        $avatar->saveAs($path, LetterAvatar::MIME_TYPE_JPEG);
        $user->roles()->sync($request->role_id);
        Alert::success("Usuario registrado correctamente!");
        return redirect()->route('users.index');
    }  
    public function changePassword(changePasswordRequest $request)
    {
        $user = User::find(auth()->user()->id);
        if (!Hash::check($request->current_password, $user->password)) {
            Alert::error('Error', 'La contraseña actual no es correcta');
            return redirect()->back()->withInput()->with('active_tab', 'change_password');
        }

        if (Hash::check($request->new_password, $user->password)) {
            Alert::error('Error', 'La nueva contraseña no puede ser igual a la contraseña actual');
            return redirect()->back()->withInput()->with('active_tab', 'change_password');
        }
   
        $passwordsComunes = ['12345678','password','admin123','qwerty123','123456789','abc123456',];

        if (in_array(strtolower($request->new_password), $passwordsComunes)) {
            Alert::error('Error', 'La contraseña ingresada es demasiado común o insegura');
            return redirect()->back()->withInput()->with('active_tab', 'change_password');
        }

        if (stripos($request->new_password, auth()->user()->name) !== false) {
            Alert::error('Error', 'La contraseña no debe contener su nombre de usuario');
            return redirect()->back()->withInput()->with('active_tab', 'change_password');
        }

        if (preg_match('/\s/', $request->new_password)) {
            Alert::error('Error', 'La contraseña no debe contener espacios');
            return redirect()->back()->withInput()->with('active_tab', 'change_password');
        }

        if (preg_match('/(.)\1{3,}/', $request->new_password)) {
            Alert::error('Error', 'La contraseña contiene demasiados caracteres repetidos');
            return redirect()->back()->withInput()->with('active_tab', 'change_password');
        }

        $user->update(['password' => bcrypt($request->new_password),]);
        Alert::success('Contraseña', 'Actualizada correctamente');
        return redirect()->back();
    }

    public function show($uuid)
    {
        $user=User::where('uuid',$uuid)->firstOrFail();
        return view('users.profile',compact('user'));
    }

   public function edit($uuid)
    {
        $user=User::where('uuid',$uuid)->firstOrFail();
        $roles=Role::where('id','>',1)->get();
        $empleados = Empleado::whereNull('deleted_at')
            ->orderBy('apellido_paterno')
            ->orderBy('nombre')
            ->get();
        return view('users.edit',compact('user','roles','empleados'));
    }

     public function update(UserRequest $request, User $user)
    {
        $usuario= User::where('id',$user->id)->first();
        $user->name=$request->name;
        $user->empleado_id = $request->empleado_id;
        $user->save();
        $user->roles()->sync($request->role_id);
        Alert::success('Datos actualizados correctamente!');
        return redirect()->route('users.index');
    }

    public function update_profile(Request $request, User $user)
    {
        $direccion = public_path('storage/avatares/');
        if (!file_exists($direccion)) {
            mkdir($direccion, 0755, true);
        }
        if ($request->remove_avatar == '1') {
            if ($user->avatar && $user->avatar !== 'default-avatar.svg') {
                $fullPath = $direccion . $user->avatar;
                if (file_exists($fullPath)) {
                    File::delete($fullPath);
                }
            }
            $user->avatar = null;
            $user->save();
            Alert::success('Foto eliminada', 'Ahora se mostrará el avatar por defecto.');
            return redirect()->back();
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && $user->avatar !== 'default-avatar.svg') {
                $fullPath = $direccion . $user->avatar;
                if (file_exists($fullPath)) {
                    File::delete($fullPath);
                }
            }
            $file_foto = $request->file('avatar');
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file_foto->getClientOriginalExtension();
            $file_foto->move($direccion, $filename);
            $user->avatar = $filename;
            $user->save();
            Alert::success('Foto actualizada', 'Tu foto de perfil fue actualizada correctamente.');
            return redirect()->back();
        }

        Alert::info('Sin cambios', 'No se realizó ningún cambio en la foto.');
        return redirect()->back();
    }
    public function destroy($uuid)
    {
        $user=User::where('uuid',$uuid)->firstOrFail();
        if ($user->avatar && $user->avatar !== 'default-avatar.svg') {
            $fullPath = public_path('storage/avatares/' . $user->avatar);
            if (file_exists($fullPath)) {
                File::delete($fullPath);
            }
        }
        $user->delete();
        Alert::success('Usuario Eliminado correctamente!');
        return redirect()->route('users.index');
    }
}
