@extends('layouts.managers')

@section('content')


    <div class="page-container ">
        <!-- START PAGE CONTENT WRAPPER -->
        <div class="page-content-wrapper ">
           
            <div class="content sm-gutter">
                <!-- START JUMBOTRON -->
                <div data-pages="parallax">
                    <div class=" container no-padding    container-fixed-lg">
                        <div class="inner">
                            <!-- START BREADCRUMB -->
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active">Dashboard</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <!-- END JUMBOTRON -->
                
                <div class="container sm-padding-10 no-padding">
                                       <div class="row">
                        <div class="col-lg-3 col-sm-12 d-flex flex-column">
                            <div class="card no-border widget-loader-bar m-b-10">
                                <div class="container-xs-height full-height">
                                    <div class="row-xs-height">
                                        <div class="col-xs-height col-top">
                                            <div class="card-header  top-left top-right">
                                                <div class="card-title">
                                                    <span class="font-montserrat fs-11 all-caps">Visitas Diarias<i
                                                            class="fa fa-chevron-right"></i>
                                                    </span>
                                                </div>
                                                <div class="card-controls">
                                                    <ul>
                                                        <li><a href="#" class="portlet-refresh text-black"
                                                                data-toggle="refresh"><i
                                                                    class="portlet-icon portlet-icon-refresh"></i></a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row-xs-height">
                                        <div class="col-xs-height col-top">
                                            <div class="p-l-20 p-t-40 p-b-10 p-r-20">
                                                <h3 class="no-margin p-b-5">{{ $viewsWeekly }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row-xs-height">
                                        <div class="col-xs-height col-bottom">
                                            <div class="progress progress-small m-b-0">
                                                <!-- START BOOTSTRAP PROGRESS (http://getbootstrap.com/components/#progress) -->
                                                <div class="progress-bar progress-bar-primary" style="width:71%"></div>
                                                <!-- END BOOTSTRAP PROGRESS -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-sm-12 d-flex flex-column">
                            <div class="card no-border widget-loader-bar m-b-10">
                                <div class="container-xs-height full-height">
                                    <div class="row-xs-height">
                                        <div class="col-xs-height col-top">
                                            <div class="card-header  top-left top-right">
                                                <div class="card-title">
                                                    <span class="font-montserrat fs-11 all-caps">Visitas Semanales<i
                                                            class="fa fa-chevron-right"></i>
                                                    </span>
                                                </div>
                                                <div class="card-controls">
                                                    <ul>
                                                        <li><a href="#" class="portlet-refresh text-black"
                                                                data-toggle="refresh"><i
                                                                    class="portlet-icon portlet-icon-refresh"></i></a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row-xs-height">
                                        <div class="col-xs-height col-top">
                                            <div class="p-l-20 p-t-40 p-b-10 p-r-20">
                                                <h3 class="no-margin p-b-5">{{ $viewsWeekly }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row-xs-height">
                                        <div class="col-xs-height col-bottom">
                                            <div class="progress progress-small m-b-0">
                                                <!-- START BOOTSTRAP PROGRESS (http://getbootstrap.com/components/#progress) -->
                                                <div class="progress-bar progress-bar-primary" style="width:71%"></div>
                                                <!-- END BOOTSTRAP PROGRESS -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-12 d-flex flex-column">
                            <div class="card no-border widget-loader-bar m-b-10">
                                <div class="container-xs-height full-height">
                                    <div class="row-xs-height">
                                        <div class="col-xs-height col-top">
                                            <div class="card-header  top-left top-right">
                                                <div class="card-title">
                                                    <span class="font-montserrat fs-11 all-caps">Visitas Mensuales<i
                                                            class="fa fa-chevron-right"></i>
                                                    </span>
                                                </div>
                                                <div class="card-controls">
                                                    <ul>
                                                        <li><a href="#" class="portlet-refresh text-black"
                                                                data-toggle="refresh"><i
                                                                    class="portlet-icon portlet-icon-refresh"></i></a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row-xs-height">
                                        <div class="col-xs-height col-top">
                                            <div class="p-l-20 p-t-40 p-b-10 p-r-20">
                                                <h3 class="no-margin p-b-5">{{ $viewsMonthly }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row-xs-height">
                                        <div class="col-xs-height col-bottom">
                                            <div class="progress progress-small m-b-0">
                                                <!-- START BOOTSTRAP PROGRESS (http://getbootstrap.com/components/#progress) -->
                                                <div class="progress-bar progress-bar-danger" style="width:15%"></div>
                                                <!-- END BOOTSTRAP PROGRESS -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-sm-12 d-flex flex-column">
                            <div class="card no-border widget-loader-bar m-b-10">
                                <div class="container-xs-height full-height">
                                    <div class="row-xs-height">
                                        <div class="col-xs-height col-top">
                                            <div class="card-header  top-left top-right">
                                                <div class="card-title">
                                                    <span class="font-montserrat fs-11 all-caps">Visitas Anual<i
                                                            class="fa fa-chevron-right"></i>
                                                    </span>
                                                </div>
                                                <div class="card-controls">
                                                    <ul>
                                                        <li><a href="#" class="portlet-refresh text-black"
                                                                data-toggle="refresh"><i
                                                                    class="portlet-icon portlet-icon-refresh"></i></a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row-xs-height">
                                        <div class="col-xs-height col-top">
                                            <div class="p-l-20 p-t-40 p-b-10 p-r-20">
                                                <h3 class="no-margin p-b-5">{{ $viewsMonthly }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row-xs-height">
                                        <div class="col-xs-height col-bottom">
                                            <div class="progress progress-small m-b-0">
                                                <!-- START BOOTSTRAP PROGRESS (http://getbootstrap.com/components/#progress) -->
                                                <div class="progress-bar progress-bar-danger" style="width:15%"></div>
                                                <!-- END BOOTSTRAP PROGRESS -->
                                            </div>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-lg-12 m-b-10 d-flex flex-column">
                          <!-- START WIDGET widget_tableRankings-->
                           <div class="widget-11-2 card no-border card-condensed no-margin widget-loader-circle d-flex flex-column align-self-stretch">
                            <div class="card-header top-right">
                              <div class="card-controls">
                                <ul>
                                  <li><a data-toggle="refresh" class="portlet-refresh text-black" href="#"><i
                              class="portlet-icon portlet-icon-refresh"></i></a>
                                  </li>
                                </ul>
                              </div>
                            </div>
                            <div class="padding-25">
                              <div class="pull-left">
                                <h2 class="text-success no-margin">Resultado</h2>
                                <p class="no-margin">Paginas Visitadas</p>
                              </div>
            
                              <div class="clearfix"></div>
                            </div>
                            <div class="auto-overflow widget-11-2-table">
                              <table class="table table-condensed table-hover">
                                <tbody>
                                  @foreach($pagesWeekly as $page)
                                  <tr>
                                    <td class="text-center font-montserrat b-r b-dashed b-grey all-caps fs-12 w-50">{{$page['pageTitle']}}</td>
                                    <td class="text-center  font-montserrat b-r b-dashed b-grey all-caps fs-12 w-50">
                                        {{$page['url']}}
                                    </td>
                                    <td class="text-center  w-25">
                                      <span class="font-montserrat fs-18">{{$page['pageViews']}}</span>
                                    </td>
                                  </tr>
                                  @endforeach
                                </tbody>
                              </table>
                            </div>
                            <div class="padding-10 mt-auto">
            
                            </div>
                          </div>
                        </div>
                      </div>
              
                

                      <div class="row">

                        <div class="col-lg-12 col-sm-12 m-b-10 d-flex flex-column">
                            <!-- START WIDGET widget_tableRankings-->
                            <div
                                class="widget-11-2 card no-border card-condensed no-margin widget-loader-circle d-flex flex-column align-self-stretch">
                                <div class="card-header top-right">
                                    <div class="card-controls">
                                        <ul>
                                            <li><a data-toggle="refresh" class="portlet-refresh text-black" href="#"><i
                                                        class="portlet-icon portlet-icon-refresh"></i></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="padding-25">
                                    <div class="pull-left mb-25">
                                        <h2 class="text-success no-margin">VISITAS</h2>
                                        <p class="">Paginas Visitadas</p>
                                    </div>

                                    <div class="containers chart-container">
                                    </div>

                                    <div class="clearfix"></div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!-- END ROW -->
                   
                    <!-- END ROW -->
                </div>

               
            </div>
                
            </div>
            <!-- END PAGE CONTENT -->
            <!-- START COPYRIGHT -->
            
            
           
            
        </div>
       
        <!-- END PAGE CONTENT WRAPPER -->
    </div>


    <script src="{{ asset('/managers/assets/plugins/jquery/jquery-3.2.1.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('/managers/assets/plugins/chart.min.js') }}" type="text/javascript"></script>


    <script>
        $(document).ready(function() {


            // =====================================
            // Salary
            // =====================================
            var order = {
                series: [
                    {
                        name: "Ordenes",
                        data: [20, 15, 30, 25, 10, 15],
                    },
                ],

                chart: {
                    toolbar: {
                        show: false,
                    },
                    height: 260,
                    type: "bar",
                    fontFamily: "Plus Jakarta Sans', sans-serif",
                    foreColor: "#adb0bb",
                },
                colors: ["#f2f6fad9", "#f2f6fad9", "var(--bs-primary)", "#f2f6fad9", "#f2f6fad9", "#f2f6fad9"],
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: "45%",
                        distributed: true,
                        endingShape: "rounded",
                    },
                },

                dataLabels: {
                    enabled: false,
                },
                legend: {
                    show: false,
                },
                grid: {
                    yaxis: {
                        lines: {
                            show: false,
                        },
                    },
                    xaxis: {
                        lines: {
                            show: false,
                        },
                    },
                },
                xaxis: {
                    categories: [["Apr"], ["May"], ["June"], ["July"], ["Aug"], ["Sept"]],
                    axisBorder: {
                        show: false,
                    },
                    axisTicks: {
                        show: false,
                    },
                },
                yaxis: {
                    labels: {
                        show: false,
                    },
                },
                tooltip: {
                    theme: "dark",
                },
            };

            var orders = new ApexCharts(document.querySelector("#orders"), order);
            orders.render();

            function createConfig() {

                return {
                    type: 'line',
                    data: {
                      labels: ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'Jul', 'aug', 'Seoptiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                      datasets: [{
                          label: 'Visitas',
                          borderColor: 'rgba(0, 0, 0, 1)',
                          backgroundColor: 'rgb(0 139 206)',
                          data: <?php echo json_encode($viewsMonthlys); ?>,
                      }]
                  },
                    options: {
                        responsive: true,
                        tooltips: {
                            position: 'nearest',
                            mode: 'index',
                            intersect: false,
                            yPadding: 10,
                            xPadding: 10,
                            caretSize: 8,
                            backgroundColor: 'rgb(0 139 206)',
                            titleFontColor: 'rgba(1, 1, 1, 1)',
                            bodyFontColor: 'rgba(1, 1, 1, 1)',
                            borderColor: 'rgba(0,0,0,1)',
                            borderWidth: 2
                        },
                    }
                };
            }

            window.onload = function() {
                var container = document.querySelector('.containers');
                var div = document.createElement('div');
                div.classList.add('chart-container');

                var canvas = document.createElement('canvas');
                div.appendChild(canvas);
                container.appendChild(div);

                var ctx = canvas.getContext('2d');
                var config = createConfig();
                new Chart(ctx, config);
            };

        });
    </script>


@endsection
