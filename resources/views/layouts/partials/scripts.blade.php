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

<!-- ===================== Intro.js: Guías interactivas ===================== -->
<script src="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/minified/intro.min.js"></script>
<script>
(function () {
    // Listener global: cualquier elemento con la clase .btn-iniciar-tour lanza un tour.
    // Los pasos se definen en el atributo data-steps (JSON) del propio botón.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-iniciar-tour');
        if (!btn) return;

        e.preventDefault();

        var raw = btn.getAttribute('data-steps');
        if (!raw) {
            console.warn('[Tour] El botón .btn-iniciar-tour no tiene atributo data-steps.');
            return;
        }

        var steps;
        try {
            steps = JSON.parse(raw);
        } catch (err) {
            console.error('[Tour] data-steps no es un JSON válido:', err);
            return;
        }

        // ¿El tour corre dentro de un modal? (atributo data-tour-modal="#idModal")
        var modalSelector = btn.getAttribute('data-tour-modal');
        var enModal = !!modalSelector;

        if (enModal) {
            document.body.classList.add('tour-en-modal');
        }

        introJs().setOptions({
            steps: steps,
            nextLabel: 'Siguiente →',
            prevLabel: '← Atrás',
            doneLabel: 'Entendido 👍',
            showProgress: true,
            showBullets: false,
            exitOnOverlayClick: !enModal, // dentro de un modal evitamos cierres accidentales
            scrollToElement: true,
            scrollTo: 'tooltip',  // desplaza hasta que el GLOBO quede visible, no solo el campo
            scrollPadding: 60
        })
        // En cada paso, aseguramos que el campo resaltado quede centrado en pantalla
        .onafterchange(function (targetEl) {
            if (targetEl && typeof targetEl.scrollIntoView === 'function') {
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
            }
        })
        .oncomplete(function () { document.body.classList.remove('tour-en-modal'); })
        .onexit(function ()     { document.body.classList.remove('tour-en-modal'); })
        .start();
    });
}());
</script>