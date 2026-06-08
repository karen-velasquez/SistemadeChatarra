<!-- Favicons -->
<link href="{{ asset('assets/img/irca.png') }}" rel="icon">
<link href="{{ asset('assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

<!-- Google Fonts -->
<link href="https://fonts.gstatic.com" rel="preconnect">
<link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

<!-- Vendor CSS Files -->
<link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/quill/quill.snow.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/quill/quill.bubble.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/select2/select2.min.css') }}" rel="stylesheet">

<!-- Template Main CSS File -->
<link href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}" rel="stylesheet">
<link href="{{ asset('assets/vendor/datatables/datatables.min.css')}}" rel="stylesheet">

<!-- Shepherd.js (guías interactivas) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/css/shepherd.css">
<style>
  /* ===== Personalización del tour (Shepherd.js) ===== */
  /* Caja del mensaje: fondo sólido, redondeada, con sombra azul del sistema */
  .shepherd-element{
    background-color: #ffffff !important;
    border: 1px solid #d8def0 !important;
    border-radius: 12px !important;
    box-shadow: 0 12px 40px rgba(1, 41, 112, .35) !important;
    max-width: 420px;
  }
  .shepherd-content{ border-radius: 12px !important; }

  /* Encabezado (cuando se usa título) */
  .shepherd-header{
    background: transparent !important;
    padding: 14px 16px 0 !important;
  }
  .shepherd-title{ color:#012970 !important; font-weight:700; font-size:1rem; }

  /* Texto del mensaje */
  .shepherd-text{
    color:#344266 !important;
    font-size:.95rem;
    line-height:1.55;
    padding: 14px 16px;
  }

  /* Flecha que apunta al elemento (combina con el fondo blanco) */
  .shepherd-arrow:before{ background:#ffffff !important; border:1px solid #d8def0; }

  /* Botones */
  .shepherd-footer{ padding: 0 16px 14px; gap: 8px; }
  .shepherd-button{
    border-radius:8px !important;
    font-weight:600;
    padding: 7px 14px;
    font-size:.85rem;
  }
  /* Botón principal (Siguiente / Entendido) en el azul del sistema */
  .shepherd-button:not(.shepherd-button-secondary){
    background:#4154f1 !important;
    color:#fff !important;
  }
  .shepherd-button:not(.shepherd-button-secondary):hover{ filter:brightness(1.07); }
  /* Botón secundario (Atrás) */
  .shepherd-button-secondary{
    background:#eef1f7 !important;
    color:#4f5d77 !important;
  }
  .shepherd-button-secondary:hover{ background:#e2e7f1 !important; }

  /* Capa oscura de fondo (modal overlay de Shepherd) */
  .shepherd-modal-overlay-container{ opacity: .6 !important; }

  /* ===== z-index sobre el modal de Bootstrap (usa ~1055) ===== */
  .shepherd-element{ z-index: 200000 !important; }
  .shepherd-modal-overlay-container{ z-index: 199990 !important; }
  /* El elemento resaltado del modal debe quedar visible sobre el overlay */
  body.tour-en-modal .modal{ z-index: 199995 !important; }

  /* ===== Fix scroll en modales durante el tour =====
     Mientras corre el tour dejamos que TODO el scroll lo maneje la página
     (el <body>), no el modal ni el .modal-body, para que el tooltip
     pueda seguir al campo resaltado al desplazarse. */
  body.tour-en-modal .modal{ overflow: visible !important; }
  body.tour-en-modal .modal-dialog{
    align-items: flex-start !important;
    min-height: auto !important;
    margin: 1.5rem auto !important;
  }
  body.tour-en-modal .modal-dialog-scrollable{
    height: auto !important;
    max-height: none !important;
  }
  body.tour-en-modal .modal-dialog-scrollable .modal-content,
  body.tour-en-modal .modal-content,
  body.tour-en-modal .modal-body{
    max-height: none !important;
    overflow: visible !important;
  }
  /* Bootstrap bloquea el scroll del body con .modal-open; lo reactivamos */
  body.tour-en-modal.modal-open{ overflow: auto !important; }
</style>
