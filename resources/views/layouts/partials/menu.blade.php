<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">

      {{-- Dashboard --}}
      <li class="nav-item">
        <a class="nav-link {{ isActiveRoute('home') }}" href="{{ route('home') }}">
          <i class="bi bi-house"></i>
          <span>Dashboard</span>
        </a>
      </li>

      {{-- CONTRATOS --}}
      @php $enContratos = request()->routeIs(['contratos.index','contratos.camiones','contratos.liquidacion','lotes_entrega.index']); @endphp
      @if(auth()->user()->can('contratos.index') || auth()->user()->can('contratos.liquidacion'))
      <li class="nav-item">
        <a class="nav-link {{ $enContratos ? '' : 'collapsed' }}"
           data-sidebar-target="menu-contratos" href="#">
          <i class="bi bi-file-earmark-text"></i>
          <span>Contratos</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="menu-contratos"
            class="nav-content sidebar-submenu {{ $enContratos ? 'submenu-open' : '' }}">
          @can('contratos.index')
          <li>
            <a href="{{ route('contratos.index') }}"
               class="{{ isActiveRoute(['contratos.index','contratos.camiones']) ? 'active' : '' }}">
              <i class="bi bi-file-earmark-text"></i><span>Ver Contratos</span>
            </a>
          </li>
          @endcan
          @can('contratos.liquidacion')
          <li>
            <a href="{{ route('contratos.liquidacion') }}"
               class="{{ isActiveRoute(['contratos.liquidacion']) ? 'active' : '' }}">
              <i class="bi bi-calculator"></i><span>Liquidación de Envíos</span>
            </a>
          </li>
          @endcan
          @can('contratos.index')
          <li>
            <a href="{{ route('lotes_entrega.index') }}"
               class="{{ isActiveRoute(['lotes_entrega.index']) ? 'active' : '' }}">
              <i class="bi bi-collection"></i><span>Lotes de Entrega</span>
            </a>
          </li>
          @endcan
        </ul>
      </li>
      @endif

      {{-- PROVEEDORES --}}
      @php $enProveedores = request()->routeIs(['proveedores.index','proveedores.create','proveedores.edit','proveedores.ficha','proveedores.consulta','pagos.proveedores.index','pagos.proveedores.pago_masivo']); @endphp
      @if(auth()->user()->can('proveedores.index') || auth()->user()->can('pagos_proveedores.index'))
      <li class="nav-item">
        <a class="nav-link {{ $enProveedores ? '' : 'collapsed' }}"
           data-sidebar-target="menu-proveedores" href="#">
          <i class="bi bi-box-seam"></i>
          <span>Proveedores</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="menu-proveedores"
            class="nav-content sidebar-submenu {{ $enProveedores ? 'submenu-open' : '' }}">
          @can('proveedores.index')
          <li>
            <a href="{{ route('proveedores.index') }}"
               class="{{ isActiveRoute(['proveedores.index','proveedores.create','proveedores.edit','proveedores.ficha','proveedores.consulta']) ? 'active' : '' }}">
              <i class="bi bi-person-lines-fill"></i><span>Lista de Proveedores</span>
            </a>
          </li>
          @endcan
          @can('pagos_proveedores.index')
          <li>
            <a href="{{ route('pagos.proveedores.index') }}"
               class="{{ isActiveRoute(['pagos.proveedores.index']) ? 'active' : '' }}">
              <i class="bi bi-cash-stack"></i><span>Pagos a proveedores</span>
            </a>
          </li>
          @endcan
          @can('pagos_proveedores.create')
          <li>
            <a href="{{ route('pagos.proveedores.pago_masivo') }}"
               class="{{ isActiveRoute(['pagos.proveedores.pago_masivo']) ? 'active' : '' }}">
              <i class="bi bi-cash-stack"></i><span>Pago Masivo</span>
            </a>
          </li>
          @endcan
        </ul>
      </li>
      @endif

      {{-- TRANSPORTE --}}
      @php $enTransporte = request()->routeIs(['camiones.index','unidades.*','seguimiento.index','pagos.camiones.index','pagos.camiones.pago_masivo']); @endphp
      @if(auth()->user()->can('camiones.index') || auth()->user()->can('unidades.index') || auth()->user()->can('seguimiento.index') || auth()->user()->can('pagos_camiones.index'))
      <li class="nav-item">
        <a class="nav-link {{ $enTransporte ? '' : 'collapsed' }}"
           data-sidebar-target="menu-transporte" href="#">
          <i class="bi bi-truck"></i>
          <span>Transporte</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="menu-transporte"
            class="nav-content sidebar-submenu {{ $enTransporte ? 'submenu-open' : '' }}">
          @can('camiones.index')
          <li>
            <a href="{{ route('camiones.index') }}"
               class="{{ isActiveRoute(['camiones.index']) ? 'active' : '' }}">
              <i class="bi bi-truck-front"></i><span>Camiones</span>
            </a>
          </li>
          @endcan
          @can('unidades.index')
          <li>
            <a href="{{ route('unidades.index') }}"
               class="{{ isActiveRoute(['unidades.index','unidades.show']) ? 'active' : '' }}">
              <i class="bi bi-truck-flatbed"></i><span>Unidades Propias</span>
            </a>
          </li>
          @endcan
          @can('seguimiento.index')
          <li>
            <a href="{{ route('seguimiento.index') }}"
               class="{{ isActiveRoute(['seguimiento.index']) ? 'active' : '' }}">
              <i class="bi bi-geo-alt"></i><span>Seguimiento de Cargas</span>
            </a>
          </li>
          @endcan
          @can('pagos_camiones.index')
          <li>
            <a href="{{ route('pagos.camiones.index') }}"
               class="{{ isActiveRoute(['pagos.camiones.index']) ? 'active' : '' }}">
              <i class="bi bi-cash-coin"></i><span>Historial Pagos</span>
            </a>
          </li>
          @endcan
          @can('pagos_camiones.create')
          <li>
            <a href="{{ route('pagos.camiones.pago_masivo') }}"
               class="{{ isActiveRoute(['pagos.camiones.pago_masivo']) ? 'active' : '' }}">
              <i class="bi bi-cash-stack"></i><span>Pago Masivo de Fletes</span>
            </a>
          </li>
          @endcan
        </ul>
      </li>
      @endif

      {{-- CLIENTES --}}
      @php $enClientes = request()->routeIs(['clientes.index','clientes.create','clientes.edit','clientes.ficha','clientes.consulta','pagos.clientes.index']); @endphp
      @if(auth()->user()->can('clientes.index') || auth()->user()->can('pagos_clientes.index'))
      <li class="nav-item">
        <a class="nav-link {{ $enClientes ? '' : 'collapsed' }}"
           data-sidebar-target="menu-clientes" href="#">
          <i class="bi bi-people"></i>
          <span>Clientes</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="menu-clientes"
            class="nav-content sidebar-submenu {{ $enClientes ? 'submenu-open' : '' }}">
          @can('clientes.index')
          <li>
            <a href="{{ route('clientes.index') }}"
               class="{{ isActiveRoute(['clientes.index','clientes.create','clientes.edit','clientes.ficha','clientes.consulta']) ? 'active' : '' }}">
              <i class="bi bi-person-lines-fill"></i><span>Lista de Clientes</span>
            </a>
          </li>
          @endcan
          @can('pagos_clientes.index')
          <li>
            <a href="{{ route('pagos.clientes.index') }}"
               class="{{ isActiveRoute(['pagos.clientes.index']) ? 'active' : '' }}">
              <i class="bi bi-receipt"></i><span>Cobros a Clientes</span>
            </a>
          </li>
          @endcan
        </ul>
      </li>
      @endif

      {{-- TESORERÍA --}}
      @if(auth()->user()->canAny(['tesoreria.index','empresas.index','prestamos_internos.index','lotes_pago.index','adquisiciones.index']))
      @php $enTesoreria = request()->routeIs(['tesoreria.*','empresas.*','prestamos_internos.*','lotes_pago.*','adquisiciones.*']); @endphp
      <li class="nav-item">
        <a class="nav-link {{ $enTesoreria ? '' : 'collapsed' }}"
           data-sidebar-target="menu-tesoreria" href="#">
          <i class="bi bi-wallet2"></i>
          <span>Tesorería</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="menu-tesoreria"
            class="nav-content sidebar-submenu {{ $enTesoreria ? 'submenu-open' : '' }}">
          @can('tesoreria.index')
          <li>
            <a href="{{ route('tesoreria.index') }}"
               class="{{ isActiveRoute(['tesoreria.index','tesoreria.cuenta']) ? 'active' : '' }}">
              <i class="bi bi-cash-stack"></i><span>Movimientos</span>
            </a>
          </li>
          @endcan
          @can('empresas.index')
          <li>
            <a href="{{ route('empresas.index') }}"
               class="{{ isActiveRoute(['empresas.index','empresas.cuentas']) ? 'active' : '' }}">
              <i class="bi bi-building"></i><span>Empresas y Cuentas</span>
            </a>
          </li>
          @endcan
          @can('prestamos_internos.index')
          <li>
            <a href="{{ route('prestamos_internos.index') }}"
               class="{{ isActiveRoute(['prestamos_internos.index']) ? 'active' : '' }}">
              <i class="bi bi-arrow-left-right"></i><span>Préstamos Internos</span>
            </a>
          </li>
          @endcan
          @can('lotes_pago.index')
          <li>
            <a href="{{ route('lotes_pago.index') }}"
               class="{{ isActiveRoute(['lotes_pago.index']) ? 'active' : '' }}">
              <i class="bi bi-collection"></i><span>Lotes de Pago</span>
            </a>
          </li>
          @endcan
          @can('adquisiciones.index')
          <li>
            <a href="{{ route('adquisiciones.index') }}"
               class="{{ isActiveRoute(['adquisiciones.index','adquisiciones.show']) ? 'active' : '' }}">
              <i class="bi bi-credit-card"></i><span>Créditos y Adquisiciones</span>
            </a>
          </li>
          @endcan
        </ul>
      </li>
      @endif

      {{-- Bancos y Cuentas --}}
      @can('bancos.index')
      <li class="nav-item">
        <a class="nav-link {{ isActiveRoute(['bancos.index']) }}" href="{{ route('bancos.index') }}">
          <i class="bi bi-bank"></i>
          <span>Bancos y Cuentas</span>
        </a>
      </li>
      @endcan

      {{-- Empleados --}}
      @can('empleados.index')
      <li class="nav-item">
        <a class="nav-link {{ isActiveRoute(['empleados.index']) }}" href="{{ route('empleados.index') }}">
          <i class="bi bi-person-badge"></i>
          <span>Empleados</span>
        </a>
      </li>
      @endcan

      @can('gastos_extras.index')
      <li class="nav-item">
        <a class="nav-link {{ isActiveRoute(['gastos_extras.index']) }}" href="{{ route('gastos_extras.index') }}">
          <i class="bi bi-cash-stack"></i>
          <span>Gastos Extra</span>
        </a>
      </li>
      @endcan

       {{-- REPORTES --}}
      @php $enReportes = request()->routeIs(['newReports.index','reportes.index','reportes.capital_utilidad']); @endphp
      @if(auth()->user()->can('reportes.index'))
      <li class="nav-item">
        <a class="nav-link {{ $enReportes ? '' : 'collapsed' }}"
           data-sidebar-target="menu-reportes" href="#">
          <i class="bi bi-people"></i>
          <span>Reportes</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="menu-reportes" class="nav-content sidebar-submenu {{ $enReportes ? 'submenu-open' : '' }}">
          
          @can('reportes.index')
          <li>
            <a href="{{ route('newReports.index') }}"
               class="{{ isActiveRoute(['newReports.index']) ? 'active' : '' }}">
              <i class="bi bi-person-lines-fill"></i><span>Reportes</span>
            </a>
          </li>
          @endcan
          <!--  
          @can('reportes.index')
          <li>
            <a href="{{ route('reportes.index') }}"
               class="{{ isActiveRoute(['reportes.index']) ? 'active' : '' }}">
              <i class="bi bi-person-lines-fill"></i><span>Reportes Generales</span>
            </a>
          </li>
          @endcan 
          @can('reportes_capital.index')
          <li>
            <a href="{{ route('reportes.capital_utilidad') }}"
               class="{{ isActiveRoute(['reportes.capital_utilidad']) ? 'active' : '' }}">
              <i class="bi bi-receipt"></i><span>Reportes de Capital y Utilidad</span>
            </a>
          </li>
          @endcan -->
        </ul>
      </li>
      @endif

      {{-- Parámetros --}}
      @can('parametros.index')
      <li class="nav-item">
        <a class="nav-link {{ isActiveRoute(['parametros.index']) }}" href="{{ route('parametros.index') }}">
          <i class="bi bi-sliders"></i>
          <span>Parámetros</span>
        </a>
      </li>
      @endcan

      {{-- Reglas de Comisión --}}
      @can('reglas_comision.index')
      <li class="nav-item">
        <a class="nav-link {{ isActiveRoute(['reglas_comision.index']) }}" href="{{ route('reglas_comision.index') }}">
          <i class="bi bi-percent"></i>
          <span>Reglas de Comisión</span>
        </a>
      </li>
      @endcan

      {{-- Reglas de Costo Adicional --}}
      @can('reglas_costo_adicional.index')
      <li class="nav-item">
        <a class="nav-link {{ isActiveRoute(['reglas_costo_adicional.index']) }}" href="{{ route('reglas_costo_adicional.index') }}">
          <i class="bi bi-cash-coin"></i>
          <span>Reglas de Costo Adicional</span>
        </a>
      </li>
      @endcan

      {{-- ADMINISTRACIÓN --}}
      @if(auth()->user()->can('permisos.index') || auth()->user()->can('roles.index') || auth()->user()->can('users.index'))
      @php $enAdmin = request()->routeIs(['permisos.*','roles.*','users.*']); @endphp
      <li class="nav-item">
        <a class="nav-link {{ $enAdmin ? '' : 'collapsed' }}"
           data-sidebar-target="menu-admin" href="#">
          <i class="bi bi-gear"></i>
          <span>Administración</span>
          <i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="menu-admin"
            class="nav-content sidebar-submenu {{ $enAdmin ? 'submenu-open' : '' }}">
          @can('permisos.index')
          <li>
            <a href="{{ route('permisos.index') }}"
               class="{{ isActiveRoute('permisos.*') ? 'active' : '' }}">
              <i class="bi bi-shield-lock"></i><span>Permisos</span>
            </a>
          </li>
          @endcan
          @can('roles.index')
          <li>
            <a href="{{ route('roles.index') }}"
               class="{{ isActiveRoute('roles.*') ? 'active' : '' }}">
              <i class="bi bi-sliders"></i><span>Roles</span>
            </a>
          </li>
          @endcan
          @can('users.index')
          <li>
            <a href="{{ route('users.index') }}"
               class="{{ isActiveRoute('users.*') ? 'active' : '' }}">
              <i class="bi bi-person-gear"></i><span>Usuarios</span>
            </a>
          </li>
          @endcan
        </ul>
      </li>
      @endif

    </ul>
</aside><!-- End Sidebar-->
