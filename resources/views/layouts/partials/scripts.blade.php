 <!-- Vendor JS Files -->
 <script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js')}}"></script>
 <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
 <script src="{{ asset('assets/vendor/chart.js/chart.umd.js')}}"></script>
 <script src="{{ asset('assets/vendor/echarts/echarts.min.js')}}"></script>
 <script src="{{ asset('assets/vendor/quill/quill.min.js')}}"></script>
 <script src="{{ asset('assets/vendor/tinymce/tinymce.min.js')}}"></script>
 <script src="{{ asset('assets/vendor/php-email-form/validate.js')}}"></script>

 <!-- Template Main JS File -->
 <script src="{{ asset('assets/js/main.js')}}"></script>
 <script src="{{ asset('assets/vendor/datatables/dataTables.min.js')}}"></script>
 <script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.all.min.js')}}"></script>
 <script src="{{ asset('assets/vendor/select2/select2.min.js')}}"></script>
<script src="{{ asset('assets/js/forms/verContrasenia.js') }}"></script>

 <script>
    
  // Función para mover el scroll
  function scrollToActiveMenu() {
    var activeMenuItem = $('#sidebar .active');
    if (activeMenuItem.length) {
      var menuSection = $('#sidebar');
      var menuSectionPosition = menuSection.offset().top;
      var activeMenuItemPosition = activeMenuItem.offset().top;
      $('.sidebar').animate({
        scrollTop: activeMenuItemPosition - menuSectionPosition - 100
      }, 100);
    }
    else{
        var activeMenuItem = $('#sidebar .menu-activo');
        if (activeMenuItem.length) {
            var menuSection = $('#sidebar');
            var menuSectionPosition = menuSection.offset().top;
            var activeMenuItemPosition = activeMenuItem.offset().top;
            $('.sidebar').animate({
                scrollTop: activeMenuItemPosition - menuSectionPosition - 100
            }, 100);
        }
    }
  }

  // Llamar a la función después de cargar la página
  scrollToActiveMenu();

  // Llamar a la función después de hacer clic en un enlace del menú
  $('#sidebar .menu-activo').on('click', function() {
    setTimeout(scrollToActiveMenu, 500);
  });
  $('#sidebar .active').on('click', function() {
    setTimeout(scrollToActiveMenu, 500);
  });


 </script>
 @include('sweetalert::alert')
 @yield('scripts')

<script>
(function () {
    document.querySelectorAll('[data-sidebar-target]').forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var targetId = toggle.getAttribute('data-sidebar-target');
            var target   = document.getElementById(targetId);
            if (!target) return;

            var isOpen = target.classList.contains('submenu-open');

            // Cerrar todos
            document.querySelectorAll('.sidebar-submenu').forEach(function(el) {
                el.classList.remove('submenu-open');
            });
            document.querySelectorAll('[data-sidebar-target]').forEach(function(l) {
                l.classList.add('collapsed');
            });

            // Si estaba cerrado, abrir
            if (!isOpen) {
                target.classList.add('submenu-open');
                toggle.classList.remove('collapsed');
            }
        });
    });
}());
</script>

<!-- ===================== Aviso de sesión por expirar ===================== -->
<script>
(function () {
    // Mismo valor que SESSION_LIFETIME (minutos) en el backend — ver config/session.php.
    // Si se cambia allá, hay que actualizar este número también.
    var SESSION_LIFETIME_MIN = {{ config('session.lifetime') }};
    var AVISO_ANTES_SEG = 120; // mostrar el aviso 2 minutos antes de expirar
    var avisoMostrado = false;
    var intervaloCuentaRegresiva = null;

    function segundosRestantes() {
        var vidaMs = SESSION_LIFETIME_MIN * 60 * 1000;
        var transcurridoMs = Date.now() - window._sesionInicioActividad;
        return Math.max(0, Math.round((vidaMs - transcurridoMs) / 1000));
    }

    function reiniciarContador() {
        window._sesionInicioActividad = Date.now();
        avisoMostrado = false;
        if (intervaloCuentaRegresiva) {
            clearInterval(intervaloCuentaRegresiva);
            intervaloCuentaRegresiva = null;
        }
        if (typeof Swal !== 'undefined' && Swal.isVisible()) {
            Swal.close();
        }
    }

    function extenderSesion() {
        fetch('{{ route('ping') }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) {
                if (!r.ok) throw new Error('No se pudo renovar la sesión');
                reiniciarContador();
            })
            .catch(function () {
                Swal.fire('Error', 'No se pudo renovar la sesión. Es posible que ya haya expirado.', 'error');
            });
    }

    function mostrarAvisoExpiracion() {
        if (avisoMostrado || typeof Swal === 'undefined') return;
        avisoMostrado = true;

        var segundos = segundosRestantes();

        Swal.fire({
            title: 'Tu sesión está por expirar',
            html: 'Por inactividad, tu sesión se cerrará en <strong><span id="sesion_contador">' + segundos + '</span></strong> segundos.<br>¿Deseas seguir conectado?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Seguir conectado',
            cancelButtonText: 'Cerrar sesión',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function () {
                intervaloCuentaRegresiva = setInterval(function () {
                    var restantes = segundosRestantes();
                    var el = document.getElementById('sesion_contador');
                    if (el) el.textContent = restantes;
                    if (restantes <= 0) {
                        clearInterval(intervaloCuentaRegresiva);
                        Swal.close();
                        window.location.reload();
                    }
                }, 1000);
            },
            willClose: function () {
                if (intervaloCuentaRegresiva) {
                    clearInterval(intervaloCuentaRegresiva);
                    intervaloCuentaRegresiva = null;
                }
            },
        }).then(function (result) {
            if (result.isConfirmed) {
                extenderSesion();
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // logout es POST (Auth::routes() de laravel/ui), no un link GET.
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('logout') }}';
                form.innerHTML = '@csrf';
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    reiniciarContador();

    setInterval(function () {
        var restantes = segundosRestantes();
        if (restantes <= AVISO_ANTES_SEG) {
            mostrarAvisoExpiracion();
        }
    }, 5000);
}());
</script>

<!-- ===================== Shepherd.js: Guías interactivas ===================== -->
<script src="https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/js/shepherd.min.js"></script>
<script>
(function () {
    // Listener global: cualquier elemento con la clase .btn-iniciar-tour lanza un tour.
    // Los pasos se definen en el atributo data-steps (JSON) del propio botón,
    // con el formato { element, intro, position }. Un adaptador los traduce
    // al formato nativo de Shepherd.js (attachTo / text), por lo que las vistas
    // NO necesitan cambiar nada al migrar de Intro.js a Shepherd.js.

    function mapPosition(pos) {
        // Intro.js usa 'bottom'/'top'/'left'/'right'; Shepherd usa lo mismo,
        // pero 'auto'/sin posición se traduce a un valor por defecto.
        var validos = ['top', 'bottom', 'left', 'right'];
        return validos.indexOf(pos) !== -1 ? pos : 'bottom';
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-iniciar-tour');
        if (!btn) return;

        e.preventDefault();

        var raw = btn.getAttribute('data-steps');
        if (!raw) {
            console.warn('[Tour] El botón .btn-iniciar-tour no tiene atributo data-steps.');
            return;
        }

        var pasos;
        try {
            pasos = JSON.parse(raw);
        } catch (err) {
            console.error('[Tour] data-steps no es un JSON válido:', err);
            return;
        }

        // ¿El tour corre dentro de un modal? (atributo data-tour-modal="#idModal")
        var enModal = !!btn.getAttribute('data-tour-modal');
        if (enModal) document.body.classList.add('tour-en-modal');

        var tour = new Shepherd.Tour({
            useModalOverlay: true,           // capa oscura con "hueco" en el elemento
            exitOnEsc: true,
            keyboardNavigation: true,
            defaultStepOptions: {
                scrollTo: { behavior: 'smooth', block: 'center' },
                cancelIcon: { enabled: true },
                modalOverlayOpeningPadding: 4,
                modalOverlayOpeningRadius: 6,
                // Evitar cierre accidental al hacer clic fuera cuando es un modal
                canClickTarget: false
            }
        });

        // Limpieza al terminar o cancelar el tour
        var limpiar = function () { document.body.classList.remove('tour-en-modal'); };
        tour.on('complete', limpiar);
        tour.on('cancel', limpiar);

        // Traducción: cada paso de data-steps -> paso de Shepherd
        pasos.forEach(function (p, i) {
            var esUltimo = i === pasos.length - 1;
            var botones = [];

            if (i > 0) {
                botones.push({
                    text: '← Atrás',
                    classes: 'shepherd-button-secondary',
                    action: function () { return this.back(); }
                });
            }
            botones.push({
                text: esUltimo ? 'Entendido 👍' : 'Siguiente →',
                action: function () { return esUltimo ? this.complete() : this.next(); }
            });

            var paso = {
                text: p.intro || '',
                buttons: botones
            };

            // Si el paso apunta a un elemento concreto, lo adjuntamos.
            // Si el elemento no existe en el DOM, se omite el attachTo y
            // el paso se muestra centrado (no rompe el tour).
            if (p.element && document.querySelector(p.element)) {
                paso.attachTo = { element: p.element, on: mapPosition(p.position) };
            }

            tour.addStep(paso);
        });

        if (tour.steps.length > 0) {
            tour.start();
        } else {
            limpiar();
        }
    });
}());
</script>

{{-- ===== Tablas: arrastrar con el mouse en horizontal y vertical ===== --}}
<script>
(function () {
    // El fixedHeader de DataTables ancla la cabecera al scroll de la página.
    // Aquí cada tabla tiene su propio scroll y la cabecera se fija con CSS sticky:
    // se desactiva para que no aparezca duplicada.
    function desactivarFixedHeader() {
        if (!window.jQuery || !jQuery.fn.dataTable) return;
        document.querySelectorAll('table').forEach(function (tabla) {
            if (!jQuery.fn.dataTable.isDataTable(tabla)) return;
            var dt = jQuery(tabla).DataTable();
            if (dt.fixedHeader) dt.fixedHeader.disable();
        });
    }

    function activarArrastre(cont) {
        if (cont.dataset.arrastreListo) return;   // no duplicar handlers
        cont.dataset.arrastreListo = '1';

        var arrastrando = false, seMovio = false;
        var xIni = 0, yIni = 0, sxIni = 0, syIni = 0;

        cont.addEventListener('mousedown', function (e) {
            if (e.button !== 0) return;               // solo botón izquierdo
            if (e.target.closest('a, button, input, select, textarea, label, .dropdown-menu')) return;

            var hayH = cont.scrollWidth  > cont.clientWidth;
            var hayV = cont.scrollHeight > cont.clientHeight;
            if (!hayH && !hayV) return;               // no hay nada que desplazar

            arrastrando = true;
            seMovio     = false;
            xIni  = e.pageX;         yIni  = e.pageY;
            sxIni = cont.scrollLeft; syIni = cont.scrollTop;
            cont.classList.add('arrastrando');
        });

        // En document y no en el contenedor: si el mouse se sale, el arrastre sigue
        document.addEventListener('mousemove', function (e) {
            if (!arrastrando) return;
            var dx = e.pageX - xIni, dy = e.pageY - yIni;
            if (Math.abs(dx) > 3 || Math.abs(dy) > 3) seMovio = true;
            cont.scrollLeft = sxIni - dx;
            cont.scrollTop  = syIni - dy;
            e.preventDefault();
        });

        document.addEventListener('mouseup', function () {
            if (!arrastrando) return;
            arrastrando = false;
            cont.classList.remove('arrastrando');
        });

        // Tras arrastrar, evitar que el gesto termine disparando un clic en la fila
        cont.addEventListener('click', function (e) {
            if (seMovio) {
                e.stopPropagation();
                e.preventDefault();
                seMovio = false;
            }
        }, true);
    }

    document.addEventListener('DOMContentLoaded', function () {
        desactivarFixedHeader();
        // Las tablas dentro de un modal usan el scroll del propio modal
        document.querySelectorAll('.table-responsive').forEach(function (cont) {
            if (cont.closest('.modal')) return;
            activarArrastre(cont);
        });
    });
}());
</script>