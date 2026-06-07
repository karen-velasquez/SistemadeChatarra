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

<!-- Intro.js (guías interactivas) -->
<link href="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/introjs.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/themes/introjs-modern.css" rel="stylesheet">
<style>
  /* ===== Personalización del tour (Intro.js) ===== */
  /* Caja del mensaje: fondo sólido y con sombra para que no se vea transparente */
  .introjs-tooltip{
    background-color: #ffffff !important;
    color: #1f2d50 !important;
    border: 1px solid #d8def0 !important;
    border-radius: 12px !important;
    box-shadow: 0 12px 40px rgba(1, 41, 112, .35) !important;
    opacity: 1 !important;
  }
  /* Encabezado / texto del mensaje */
  .introjs-tooltiptext{
    color: #344266 !important;
    font-size: .95rem;
    line-height: 1.5;
  }
  /* Flechita que apunta al elemento: que combine con el fondo blanco */
  .introjs-arrow.top{ border-bottom-color:#ffffff !important; }
  .introjs-arrow.bottom{ border-top-color:#ffffff !important; }
  .introjs-arrow.left{ border-right-color:#ffffff !important; }
  .introjs-arrow.right{ border-left-color:#ffffff !important; }

  /* Capa oscura de fondo: un poco más opaca para que resalte el mensaje */
  .introjs-overlay{ opacity: .65 !important; }

  /* Botones en el color azul del sistema */
  .introjs-button{
    text-shadow:none !important;
    border-radius:8px !important;
    font-weight:600;
  }
  .introjs-tooltipbuttons .introjs-nextbutton{
    background:#4154f1 !important;
    color:#fff !important;
    border-color:#4154f1 !important;
  }
  .introjs-tooltipbuttons .introjs-nextbutton:hover{ filter:brightness(1.07); }

  /* Barra y puntos de progreso en azul */
  .introjs-progressbar{ background-color:#4154f1 !important; }
  .introjs-bullets ul li a.active{ background:#4154f1 !important; }

  /* Cuando el tour corre DENTRO de un modal de Bootstrap, elevar Intro.js
     por encima del modal (Bootstrap usa z-index 1055/1050). */
  body.tour-en-modal .introjs-tooltip,
  body.tour-en-modal .introjs-helperLayer,
  body.tour-en-modal .introjs-tooltipReferenceLayer{
    z-index: 200000 !important;
  }
  body.tour-en-modal .introjs-overlay{ z-index: 199990 !important; }
  /* El elemento resaltado del modal debe quedar visible sobre el overlay */
  body.tour-en-modal .modal{ z-index: 199995 !important; }

  /* ===== Fix scroll en modales durante el tour =====
     Mientras corre el tour dejamos que TODO el scroll lo maneje la página
     (el <body>), no el modal ni el .modal-body. Así Intro.js, que calcula
     posiciones respecto al documento, puede desplazar hasta el campo
     resaltado y el globo lo acompaña. */

  /* El modal deja de capturar el scroll y de centrar verticalmente:
     fluye de arriba hacia abajo y crece con su contenido. */
  body.tour-en-modal .modal{
    overflow: visible !important;
  }
  body.tour-en-modal .modal-dialog{
    align-items: flex-start !important;   /* anula el centrado vertical */
    min-height: auto !important;
    margin: 1.5rem auto !important;
  }
  /* Modales scrollables (modal-dialog-scrollable): quitar su límite de alto */
  body.tour-en-modal .modal-dialog-scrollable{
    height: auto !important;
    max-height: none !important;
  }
  body.tour-en-modal .modal-dialog-scrollable .modal-content{
    max-height: none !important;
    overflow: visible !important;
  }
  /* El cuerpo del modal NO scrollea: crece y empuja el scroll de la página */
  body.tour-en-modal .modal-content,
  body.tour-en-modal .modal-body{
    max-height: none !important;
    overflow: visible !important;
  }
  /* Bootstrap bloquea el scroll del body con .modal-open (overflow:hidden).
     Durante el tour lo reactivamos para que la página pueda desplazarse. */
  body.tour-en-modal.modal-open{
    overflow: auto !important;
  }
</style>
