<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ingreso | Gestión Inteligente de Exportación de Chatarra</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{
      --verde-oscuro:#0F5132;
      --verde-secundario:#146C43;
      --verde-hover:#0B3D26;
      --gris-fondo:#F4F6F5;
      --texto:#263238;
    }
    *{box-sizing:border-box;margin:0;padding:0;}
    html,body{height:100%;}
    body{
      font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;
      background:var(--gris-fondo);
      color:var(--texto);
      overflow-x:hidden;
    }

    .login-shell{
      position:relative;
      min-height:100vh;
      width:100%;
      overflow:hidden;
    }

    /* ===================== FONDO — PANTALLA COMPLETA ===================== */
    .login-hero{
      position:absolute;
      inset:0;
      width:100%;
      min-height:100vh;
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      overflow:hidden;
      background:#0b2118 url('{{ asset("assets/images/login/background.png") }}') center center / cover no-repeat;
      z-index:0;
    }

    /* Parallax muy ligero: la imagen de fondo se desplaza levemente con el mouse via CSS var */
    .login-hero__bg{
      position:absolute;
      inset:-20px;
      background:inherit;
      background-position:center;
      background-size:cover;
      transform:translate(var(--px,0), var(--py,0));
      transition:transform .6s ease-out;
      z-index:0;
    }

    /* Overlay oscuro + gradiente animado muy lento */
    .login-hero__overlay{
      position:absolute;
      inset:0;
      z-index:1;
      background:linear-gradient(135deg, rgba(6,26,17,.92) 0%, rgba(15,81,50,.75) 45%, rgba(6,26,17,.90) 100%);
      background-size:200% 200%;
      animation:gradientShift 18s ease-in-out infinite;
    }
    @keyframes gradientShift{
      0%{background-position:0% 50%;}
      50%{background-position:100% 50%;}
      100%{background-position:0% 50%;}
    }

    /* Partículas sutiles */
    .login-hero__particles{
      position:absolute;
      inset:0;
      z-index:2;
      pointer-events:none;
    }
    .particle{
      position:absolute;
      width:6px;
      height:6px;
      border-radius:50%;
      background:rgba(255,255,255,.35);
      filter:blur(1.5px);
      animation:floatParticle linear infinite;
    }
    @keyframes floatParticle{
      0%{transform:translateY(0) translateX(0);opacity:0;}
      10%{opacity:.6;}
      90%{opacity:.6;}
      100%{transform:translateY(-120px) translateX(20px);opacity:0;}
    }

    .login-hero__content{
      position:relative;
      z-index:3;
      padding:64px 72px 40px;
      max-width:56%;
      color:#fff;
    }

    .login-hero__title{
      font-size:2.75rem;
      font-weight:800;
      line-height:1.15;
      max-width:620px;
      letter-spacing:-.02em;
      text-shadow:0 2px 24px rgba(0,0,0,.35);
    }

    .login-hero__subtitle{
      margin-top:20px;
      font-size:1.1rem;
      font-weight:400;
      color:rgba(255,255,255,.88);
      max-width:520px;
      line-height:1.6;
    }

    /* Features inferiores */
    .login-hero__features{
      position:relative;
      z-index:3;
      display:flex;
      flex-wrap:wrap;
      gap:16px 28px;
      max-width:56%;
      padding:0 72px 56px;
    }
    .feature{
      display:flex;
      align-items:center;
      gap:10px;
      color:#fff;
    }
    .feature__icon{
      width:40px;
      height:40px;
      flex-shrink:0;
      border-radius:11px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:rgba(255,255,255,.12);
      border:1px solid rgba(255,255,255,.18);
      transition:transform .2s ease;
    }
    .feature:hover .feature__icon{
      transform:scale(1.10);
    }
    .feature__icon svg{
      width:20px;
      height:20px;
      stroke:#fff;
    }
    .feature__label{
      font-size:.88rem;
      font-weight:600;
      letter-spacing:.01em;
      white-space:nowrap;
    }

    /* ===================== FORMULARIO — FLOTA SOBRE EL FONDO ===================== */
    .login-panel{
      position:relative;
      z-index:4;
      width:100%;
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:flex-end;
      padding:40px 8vw;
    }

    .login-card{
      width:100%;
      max-width:420px;
      background:rgba(255,255,255,.94);
      backdrop-filter:blur(18px);
      -webkit-backdrop-filter:blur(18px);
      border:1px solid rgba(255,255,255,.7);
      border-radius:24px;
      box-shadow:0 24px 70px rgba(0,0,0,.28), 0 4px 16px rgba(0,0,0,.10);
      padding:48px 40px;
      opacity:0;
      transform:translateY(20px);
      animation:cardIn .7s ease-out forwards;
    }
    @keyframes cardIn{
      to{opacity:1;transform:translateY(0);}
    }

    .login-card__icon{
      width:64px;
      height:64px;
      margin:0 auto 24px;
      border-radius:50%;
      background:var(--verde-oscuro);
      display:flex;
      align-items:center;
      justify-content:center;
      box-shadow:0 8px 24px rgba(15,81,50,.35);
      animation:floatLock 4s ease-in-out infinite;
    }
    @keyframes floatLock{
      0%,100%{transform:translateY(0);}
      50%{transform:translateY(-3px);}
    }
    .login-card__icon svg{
      width:28px;
      height:28px;
      stroke:#fff;
    }

    .login-card__title{
      text-align:center;
      font-size:1.6rem;
      font-weight:700;
      color:var(--texto);
      margin-bottom:6px;
    }
    .login-card__subtitle{
      text-align:center;
      font-size:.92rem;
      color:#64748b;
      margin-bottom:32px;
      line-height:1.5;
    }

    .field{
      margin-bottom:18px;
    }
    .field label{
      display:block;
      font-size:.85rem;
      font-weight:600;
      color:var(--texto);
      margin-bottom:6px;
    }
    .field__control{
      position:relative;
      display:flex;
      align-items:center;
    }
    .field__control svg.field__icon{
      position:absolute;
      left:14px;
      width:19px;
      height:19px;
      stroke:#94a3b8;
      pointer-events:none;
      transition:stroke .2s ease;
    }
    .field__control input{
      width:100%;
      padding:12px 14px 12px 44px;
      font-size:.95rem;
      font-family:inherit;
      color:var(--texto);
      background:#fff;
      border:1.5px solid #e2e8f0;
      border-radius:12px;
      outline:none;
      transition:border-color .2s ease, box-shadow .2s ease;
    }
    .field__control input::placeholder{
      color:#b0bec5;
    }
    .field__control input:focus{
      border-color:var(--verde-secundario);
      box-shadow:0 0 0 4px rgba(20,108,67,.12);
    }
    .field__control input:focus ~ svg.field__icon{
      stroke:var(--verde-secundario);
    }
    .field__toggle{
      position:absolute;
      right:14px;
      cursor:pointer;
      color:#94a3b8;
      display:flex;
      align-items:center;
      background:none;
      border:none;
      padding:0;
    }
    .field__toggle svg{width:19px;height:19px;stroke:currentColor;}
    .field__toggle:hover{color:var(--verde-secundario);}

    .field-error{
      margin-top:6px;
      font-size:.8rem;
      color:#dc2626;
      font-weight:500;
    }

    .remember-row{
      display:flex;
      align-items:center;
      gap:8px;
      margin-bottom:26px;
    }
    .remember-row input[type="checkbox"]{
      width:17px;
      height:17px;
      accent-color:var(--verde-oscuro);
      cursor:pointer;
    }
    .remember-row label{
      font-size:.88rem;
      color:#475569;
      cursor:pointer;
      user-select:none;
    }

    .btn-login{
      width:100%;
      padding:14px;
      font-size:.98rem;
      font-weight:700;
      color:#fff;
      background:var(--verde-oscuro);
      border:none;
      border-radius:12px;
      cursor:pointer;
      letter-spacing:.01em;
      transition:transform .25s ease, box-shadow .25s ease, background .25s ease;
      box-shadow:0 4px 14px rgba(15,81,50,.28);
    }
    .btn-login:hover{
      background:var(--verde-hover);
      transform:translateY(-2px);
      box-shadow:0 10px 24px rgba(15,81,50,.35);
    }
    .btn-login:active{
      transform:translateY(0);
    }

    /* ===================== RESPONSIVE ===================== */
    @media (max-width: 992px){
      .login-hero__content{padding:40px 32px 16px;max-width:100%;}
      .login-hero__title{font-size:1.9rem;}
      .login-hero__subtitle{font-size:1rem;}
      .login-hero__features{
        max-width:100%;
        grid-template-columns:unset;
        display:grid;
        grid-template-columns:repeat(2, 1fr);
        gap:14px 16px;
        padding:0 32px 24px;
      }
      .login-panel{justify-content:center;padding:24px 20px;align-items:flex-end;}
    }
    @media (max-width: 480px){
      .login-card{padding:32px 22px;}
      .login-hero__content{display:none;} /* en móvil muy chico prioriza el formulario sobre el texto */
      .login-hero__features{display:none;} /* idem: las features no caben con el formulario encima en pantallas tan chicas */
    }
  </style>
</head>
<body>

  <div class="login-shell">

    {{-- ===================== LADO IZQUIERDO ===================== --}}
    <div class="login-hero" id="loginHero">
      <div class="login-hero__bg" id="loginHeroBg"></div>
      <div class="login-hero__overlay"></div>
      <div class="login-hero__particles" id="loginParticles"></div>

      <div class="login-hero__content">
        <h1 class="login-hero__title">Gestión inteligente para la exportación de chatarra</h1>
        <p class="login-hero__subtitle">Controla cargas, viajes, clientes, exportaciones y logística desde una sola plataforma.</p>
      </div>

      <div class="login-hero__features">
        <div class="feature">
          <span class="feature__icon">
            {{-- Heroicon: truck --}}
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25h5.634a1.5 1.5 0 0 1 1.417 1.005l1.75 5.24a1.5 1.5 0 0 1-1.417 1.995H16.5M14.25 7.5v6.75m0-6.75H3.375A1.125 1.125 0 0 0 2.25 8.625v5.625M14.25 14.25H3.375"/></svg>
          </span>
          <span class="feature__label">Transporte</span>
        </div>
        <div class="feature">
          <span class="feature__icon">
            {{-- Heroicon: shield-check --}}
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12.75 11.25 15 15 9.75M12 3c-2.755 0-5.455.232-8.083.678A1.5 1.5 0 0 0 2.75 4.98l.001 3.988c0 6.05 3.87 11.293 9.249 13.267 5.379-1.974 9.249-7.216 9.249-13.267V4.98a1.5 1.5 0 0 0-1.167-1.302A48.708 48.708 0 0 0 12 3Z"/></svg>
          </span>
          <span class="feature__label">Seguridad</span>
        </div>
        <div class="feature">
          <span class="feature__icon">
            {{-- Heroicon: globe-americas --}}
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"/><path d="M3.6 9h16.8M3.6 15h16.8M12 3a15.3 15.3 0 0 1 3 9 15.3 15.3 0 0 1-3 9 15.3 15.3 0 0 1-3-9 15.3 15.3 0 0 1 3-9Z"/></svg>
          </span>
          <span class="feature__label">Exportación</span>
        </div>
        <div class="feature">
          <span class="feature__icon">
            {{-- Heroicon: chart-bar --}}
            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13.125c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
          </span>
          <span class="feature__label">Control en tiempo real</span>
        </div>
      </div>
    </div>

    {{-- ===================== LADO DERECHO ===================== --}}
    <div class="login-panel">
      <div class="login-card">

        <div class="login-card__icon">
          {{-- Heroicon: lock-closed --}}
          <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
        </div>

        <h2 class="login-card__title">Bienvenido</h2>
        <p class="login-card__subtitle">Ingrese sus credenciales para acceder al sistema.</p>

        <form method="POST" action="{{ route('login') }}" novalidate>
          @csrf

          <div class="field">
            <label for="email">Correo electrónico</label>
            <div class="field__control">
              <svg class="field__icon" viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.25 6.75c0-.621.504-1.125 1.125-1.125h17.25c.621 0 1.125.504 1.125 1.125v10.5c0 .621-.504 1.125-1.125 1.125H3.375A1.125 1.125 0 0 1 2.25 17.25V6.75Z"/><path d="m3 7 9 6 9-6"/></svg>
              <input type="email" name="email" id="email" placeholder="correo@empresa.com"
                     value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>
            @error('email')<p class="field-error">{{ $message }}</p>@enderror
          </div>

          <div class="field">
            <label for="password">Contraseña</label>
            <div class="field__control">
              <svg class="field__icon" viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
              <input type="password" name="password" id="password" placeholder="••••••••"
                     required autocomplete="current-password" style="padding-right:44px;">
              <button type="button" class="field__toggle" id="togglePassword" aria-label="Mostrar contraseña">
                <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
              </button>
            </div>
            @error('password')<p class="field-error">{{ $message }}</p>@enderror
          </div>

          <div class="remember-row">
            <input type="checkbox" name="remember" value="true" id="rememberMe">
            <label for="rememberMe">Recordarme</label>
          </div>

          <button type="submit" class="btn-login">Ingresar al sistema</button>
        </form>

      </div>
    </div>

  </div>

  <script>
    // Toggle mostrar/ocultar contraseña
    document.getElementById('togglePassword').addEventListener('click', function () {
      const input = document.getElementById('password');
      const isPwd = input.type === 'password';
      input.type = isPwd ? 'text' : 'password';
    });

    // Parallax muy ligero sobre la imagen de fondo, según posición del mouse
    (function () {
      const hero = document.getElementById('loginHero');
      const bg   = document.getElementById('loginHeroBg');
      if (!hero || !bg || window.matchMedia('(max-width: 992px)').matches) return;

      hero.addEventListener('mousemove', function (e) {
        const rect = hero.getBoundingClientRect();
        const relX = (e.clientX - rect.left) / rect.width  - 0.5; // -0.5 a 0.5
        const relY = (e.clientY - rect.top)  / rect.height - 0.5;
        bg.style.setProperty('--px', (relX * -10) + 'px');
        bg.style.setProperty('--py', (relY * -8) + 'px');
      });
      hero.addEventListener('mouseleave', function () {
        bg.style.setProperty('--px', '0px');
        bg.style.setProperty('--py', '0px');
      });
    })();

    // Partículas sutiles flotando hacia arriba
    (function () {
      const container = document.getElementById('loginParticles');
      if (!container) return;
      const TOTAL = 7;
      for (let i = 0; i < TOTAL; i++) {
        const p = document.createElement('span');
        p.className = 'particle';
        const size = 4 + Math.random() * 4;
        p.style.width  = size + 'px';
        p.style.height = size + 'px';
        p.style.left   = Math.random() * 100 + '%';
        p.style.top    = (40 + Math.random() * 55) + '%';
        p.style.animationDuration = (14 + Math.random() * 10) + 's';
        p.style.animationDelay    = (Math.random() * 10) + 's';
        container.appendChild(p);
      }
    })();
  </script>

</body>
</html>
