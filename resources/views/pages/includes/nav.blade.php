
<!-- Sidebar Start -->

<aside class="left-sidebar">
    <!-- Sidebar scroll-->
    <div>

        <!-- Sidebar navigation-->
        <nav class="sidebar-nav scroll-sidebar" data-simplebar>
            <ul id="sidebarnav">
                <li class="nav-small-cap">

                    <span class="hide-menu">Inicio</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('manager.dashboard') }}" aria-expanded="false">
                  <span>
                    <i class="fa-duotone fa-house"></i>
                  </span>
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>
                <li class="nav-small-cap">
                    <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                    <span class="hide-menu">Contenido</span>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow " href="#" aria-expanded="false">
                          <span class="d-flex">
                            <i class="fa-duotone fa-note"></i>
                          </span>
                          <span class="hide-menu">Noticias</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.blogs') }}" aria-expanded="false">
                                <span>
                                  <i class="ti ti-circle"></i>
                                </span>
                                <span class="hide-menu">Noticias</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.blogs.categories') }}" aria-expanded="false">
                                  <span>
                                    <i class="ti ti-circle"></i>
                                  </span>
                                <span class="hide-menu">Categorias</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.blogs.tags') }}" aria-expanded="false">
                              <span>
                                <i class="ti ti-circle"></i>
                              </span>
                                <span class="hide-menu">Etiquetas</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link has-arrow "  aria-expanded="false">
                          <span class="d-flex">
                            <i class="fa-duotone fa-ballot-check"></i>
                          </span>
                        <span class="hide-menu">Grupos</span>
                    </a>
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a class="sidebar-link"href="{{ route('manager.groups') }}"  aria-expanded="false">
                                <span>
                                  <i class="ti ti-circle"></i>
                                </span>
                                <span class="hide-menu">Grupos</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.groups.types') }}" aria-expanded="false">
                                  <span>
                                    <i class="ti ti-circle"></i>
                                  </span>
                                <span class="hide-menu">Tipos</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.groups.permissions') }}" aria-expanded="false">
                                  <span>
                                    <i class="ti ti-circle"></i>
                                  </span>
                                <span class="hide-menu">Permisos</span>
                            </a>
                        </li>

                    </ul>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('manager.interesteds') }}" aria-expanded="false">
                          <span class="d-flex">
                            <i class="fa-duotone fa-ballot-check"></i>
                          </span>
                        <span class="hide-menu">Interesados</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('manager.plans') }}" aria-expanded="false">
                          <span class="d-flex">
                            <i class="fa-duotone fa-ballot-check"></i>
                          </span>
                        <span class="hide-menu">Planes</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('manager.coupons') }}" aria-expanded="false">
                          <span class="d-flex">
                            <i class="fa-duotone fa-ballot-check"></i>
                          </span>
                        <span class="hide-menu">Cupones</span>
                    </a>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('manager.orders') }}" aria-expanded="false">
                          <span class="d-flex">
                            <i class="fa-duotone fa-memo-pad"></i>
                          </span>
                        <span class="hide-menu">Ordenes</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('manager.testimonies') }}" aria-expanded="false">
                          <span class="d-flex">
                           <i class="fa-duotone fa-message-smile"></i>
                          </span>
                        <span class="hide-menu">Testimonios</span>
                    </a>
                </li>



                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('manager.contacts') }}" aria-expanded="false">
                          <span class="d-flex">
                           <i class="fa-duotone fa-envelope"></i>
                          </span>
                        <span class="hide-menu">Contactenos</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('manager.trusteds') }}" aria-expanded="false">
                          <span class="d-flex">
                            <i class="fa-duotone fa-circle-parking"></i>
                          </span>
                        <span class="hide-menu">Aliados</span>
                    </a>
                </li>


                <li class="nav-small-cap">
                    <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                    <span class="hide-menu">Plataforma</span>
                </li>

                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('manager.users') }}" aria-expanded="false">
                          <span class="d-flex">
                            <i class="fa-duotone fa-user-vneck-hair"></i>
                          </span>
                        <span class="hide-menu">Usuarios</span>
                    </a>
                </li>

                <li class="nav-small-cap">
                    <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                    <span class="hide-menu">Configuración</span>
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
                            <a class="sidebar-link"  href="{{ route('manager.settings.analytics') }}" aria-expanded="false">
                                <span>
                                  <i class="ti ti-circle"></i>
                                </span>
                                <span class="hide-menu">Google Analytics</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link"  href="{{ route('manager.settings.pixel') }}" aria-expanded="false">
                                  <span>
                                    <i class="ti ti-circle"></i>
                                  </span>
                                <span class="hide-menu">Pixel Analytics</span>
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
                            <a class="sidebar-link"  href="{{ route('manager.settings.metadata') }}" aria-expanded="false">
                                  <span>
                                    <i class="ti ti-circle"></i>
                                  </span>
                                <span class="hide-menu">Seo</span>
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
                    </ul>
                </li>


                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ route('manager.analytics') }}" aria-expanded="false">
                          <span class="d-flex">
                           <i class="fa-duotone fa-square-poll-vertical"></i>
                          </span>
                        <span class="hide-menu">Estadisticas</span>
                    </a>
                </li>

            </ul>

        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
</aside>

<!-- Sidebar End -->


