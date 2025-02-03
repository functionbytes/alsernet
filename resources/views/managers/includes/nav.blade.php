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
                    <a class="sidebar-link has-arrow " href="#" aria-expanded="false">
                          <span class="d-flex">
                            <i class="fa-duotone fa-note"></i>
                          </span>
                        <span class="hide-menu">Suscriptiones</span>
                    </a>


                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="{{ route('manager.subscribers') }}" class="sidebar-link">
                                <span class="hide-menu">Suscriptiones </span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('manager.subscribers.lists') }}" class="sidebar-link">
                                <span class="hide-menu">Listas</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="{{ route('manager.subscribers.conditions') }}" class="sidebar-link">
                                <span class="hide-menu">Estados</span>
                            </a>
                        </li>
                    </ul>

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


                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow " href="#" aria-expanded="false">
                          <span class="d-flex">
                            <i class="fa-duotone fa-note"></i>
                          </span>
                        <span class="hide-menu">Ticket</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.tickets') }}" aria-expanded="false">
                                <span>
                                  <i class="ti ti-circle"></i>
                                </span>
                                <span class="hide-menu">Tickets</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="{{ route('manager.tickets.categories') }}" aria-expanded="false">
                                  <span>
                                    <i class="ti ti-circle"></i>
                                  </span>
                                <span class="hide-menu">Categoria</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="{{ route('manager.tickets.status') }}" aria-expanded="false">
                                  <span>
                                    <i class="ti ti-circle"></i>
                                  </span>
                                <span class="hide-menu">Estado</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.tickets.priorities') }}" aria-expanded="false">
                              <span>
                                <i class="ti ti-circle"></i>
                              </span>
                                <span class="hide-menu">Prioridad</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.tickets.groups') }}" aria-expanded="false">
                              <span>
                                <i class="ti ti-circle"></i>
                              </span>
                                <span class="hide-menu">Grupos</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="{{ route('manager.tickets.canneds') }}" aria-expanded="false">
                            <span>
                              <i class="ti ti-circle"></i>
                            </span>
                                <span class="hide-menu">Respuestas</span>
                            </a>
                        </li>
                    </ul>
                </li>



                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow " href="#" aria-expanded="false">
                          <span class="d-flex">
                            <i class="fa-duotone fa-circle-exclamation"></i>
                          </span>
                        <span class="hide-menu">Preguntas</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.faqs') }}" aria-expanded="false">
                                <span>
                                  <i class="ti ti-circle"></i>
                                </span>
                                <span class="hide-menu">Preguntas</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.faqs.categories') }}" aria-expanded="false">
                                <span>
                                  <i class="ti ti-circle"></i>
                                </span>
                                <span class="hide-menu">Categorias</span>
                            </a>
                        </li>
                    </ul>
                </li>




                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow " href="#" aria-expanded="false">
                          <span class="d-flex">
                           <i class="fa-duotone fa-gear-code"></i>
                          </span>
                        <span class="hide-menu">Configuración</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.settings') }}" aria-expanded="false">
                                <span>
                                  <i class="ti ti-circle"></i>
                                </span>
                                <span class="hide-menu">Configuración</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.settings.emails') }}" aria-expanded="false">
                                  <span>
                                    <i class="ti ti-circle"></i>
                                  </span>
                                <span class="hide-menu">Smtp</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.settings.tickets') }}" aria-expanded="false">
                                  <span>
                                    <i class="ti ti-circle"></i>
                                  </span>
                                <span class="hide-menu">Ticket</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.livechats') }}" aria-expanded="false">
                                  <span>
                                    <i class="ti ti-circle"></i>
                                  </span>
                                <span class="hide-menu">Chat</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.settings.hours') }}" aria-expanded="false">
                                  <span>
                                    <i class="ti ti-circle"></i>
                                  </span>
                                <span class="hide-menu">Horario</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.settings.maintenance') }}" aria-expanded="false">
                              <span>
                                <i class="ti ti-circle"></i>
                              </span>
                                <span class="hide-menu">Mantenimiento</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.templates') }}" aria-expanded="false">
                                  <span>
                                    <i class="ti ti-circle"></i>
                                  </span>
                                <span class="hide-menu">Plantillas campaña</span>
                            </a>
                        </li>

                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.layouts') }}" aria-expanded="false">
                                  <span>
                                    <i class="ti ti-circle"></i>
                                  </span>
                                <span class="hide-menu">Plantilla correos</span>
                            </a>
                        </li>


                    </ul>
                </li>


            </ul>
        </nav>
        <!-- End Sidebar navigation -->
    </div>

</aside>

<!-- Sidebar End -->

