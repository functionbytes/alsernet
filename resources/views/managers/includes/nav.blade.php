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
                    <a class="sidebar-link" href="{{ route('manager.dashboard') }}" aria-expanded="false">
                  <span>
                    <i class="fa-duotone fa-house"></i>
                  </span>
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>


                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('manager.inventaries') }}" aria-expanded="false">
                          <span class="d-flex">
                           <i class="fa-duotone fa-barcode"></i>
                          </span>
                        <span class="hide-menu">Inventaries</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('manager.products') }}" aria-expanded="false">
                          <span class="d-flex">
                          <i class="fa-sharp-duotone fa-solid fa-typewriter"></i>
                          </span>
                        <span class="hide-menu">Productos</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('manager.shops') }}" aria-expanded="false">
                          <span class="d-flex">
                            <i class="fa-duotone fa-home-alt"></i>
                          </span>
                        <span class="hide-menu">Tiendas</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('manager.users') }}" aria-expanded="false">
                          <span class="d-flex">
                           <i class="fa-duotone fa-user"></i>
                          </span>
                        <span class="hide-menu">Usuarios</span>
                    </a>
                </li>

            </ul>
        </nav>
        <!-- End Sidebar navigation -->
    </div>

</aside>

<!-- Sidebar End -->

