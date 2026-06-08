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