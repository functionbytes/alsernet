<!-- Sidebar Start -->

<aside class="left-sidebar">
  <!-- Sidebar scroll-->
  <div>
    <!-- Sidebar navigation-->
    <nav class="sidebar-nav scroll-sidebar container-fluid">
      <ul id="sidebarnav">
        <!-- ============================= -->
        <!-- Home -->
        <!-- ============================= -->
        <li class="nav-small-cap">
          <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
        </li>
        <!-- =================== -->
        <!-- Dashboard -->
        <!-- =================== -->
        <li class="sidebar-item">
          <a class="sidebar-link " href="{{  route('home') }}" aria-expanded="false">
            <span>
              <i class="fa-duotone fa-house"></i>
            </span>
            <span class="hide-menu">Inicio</span>
          </a>
        </li>
        <li class="sidebar-item">
          <a class="sidebar-link " href="{{ route('inventarie.inventaries') }}" aria-expanded="false">
            <span>
              <i class="fa-duotone fa-house-medical-circle-check"></i>
            </span>
            <span class="hide-menu">Inventarios</span>
          </a>
        </li>

      </ul>
    </nav>
    <!-- End Sidebar navigation -->
  </div>

</aside>

<!-- Sidebar End -->

