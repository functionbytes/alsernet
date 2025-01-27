@extends('layouts.managers')

@section('content')

    <div class="row">
        <div class="col-lg-12 d-flex align-items-stretch">

            <div class="card w-100">

                <form id="formHours" enctype="multipart/form-data" role="form" onSubmit="return false">

                    {{ csrf_field() }}


                    <div class="card-body border-top">
                        <div class=" row align-items-center">
                            <div class=" col-sm-11 ">
                                <h5 class="mb-3">Horario soporte</h5>
                                <p class="card-subtitle mb-3 mt-0">(Si "habilita" esta configuración, los clientes solo podrán ver el nombre que proporcione en el campo de entrada a continuación. No podrán ver el nombre ni la función de los empleados).</p>
                            </div>
                            <div class="col-sm-1 justify-content-end d-flex align-items">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="hoursswitch" id="hoursswitch"   @if(setting('hoursswitch')=='true' ) checked @endif/>
                                </div>
                            </div>
                        </div>

                        <div class="row">

                            <div class="col-12">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Titulo</label>
                                        <input type="text" class="form-control" id="hourstitle"  name="hourstitle" value="{{ setting('hourstitle') }}" placeholder="Ingresar titulo">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                        <label  class="control-label col-form-label">Subtitulo</label>
                                        <input type="text" class="form-control" id="hourssubtitle"  name="hourssubtitle" value="{{ setting('hourssubtitle') }}" placeholder="Ingresar subtitulo">

                                </div>
                            </div>

                        </div>

                    </div>


                    <div class="card-body border-top">

                        <div class="row mt-50">

                            <div class="col-12 ">
                                <div class="mb-4 mt-3">
                                    <div class=" row align-items-center">
                                        <div class=" col-sm-11 ">
                                            <h5 class="mb-3">Horarios</h5>
                                            <p class="card-subtitle mb-3 mt-0">(Este sera el horario que vera todos los usuarios al momento de solicitar soporte).</p>
                                        </div>
                                    </div>
                                    <div class=" row align-items-center">
                                        <div class="table-responsive table-bussiness-hours">
                                            <table class="table card-table table-vcenter text-nowrap mb-0">
                                                <thead>
                                                <tr class="">
                                                    <th class="w-20 border-bottom-0 ">Dia</th>
                                                    <th class="w-20 border-bottom-0">Estado</th>
                                                    <th class="w-20 border-bottom-0">Hora apertura</th>
                                                    <th class="w-20 border-bottom-0">Hora cierre</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @php
                                                    $timestart = ['12:00 AM','12:30 AM','1:00 AM','1:30 AM','2:00 AM','2:30 AM','3:00 AM','3:30 AM','4:00 AM','4:30 AM','5:00 AM','5:30 AM','6:00 AM','6:30 AM','7:00 AM','7:30 AM','8:00 AM','8:30 AM','9:00 AM','9:30 AM','10:00 AM','10:30 AM','11:00 AM','11:30 AM','12:00 PM','12:30 PM','1:00 PM','1:30 PM','2:00 PM','2:30 PM','3:00 PM','3:30 PM','4:00 PM','4:30 PM','5:00 PM','5:30 PM','6:00 PM','6:30 PM','7:00 PM','7:30 PM','8:00 PM','8:30 PM','9:00 PM','9:30 PM','10:00 PM','10:30 PM','11:00 PM','11:30 PM'];
                                                @endphp
                                                <tr class="border-bottom-transparent">
                                                    <td class="">
                                                        <input type="hidden" name="bussinessid1" value="1">
                                                        <select name="bussiness1"
                                                                class="form-control select2 select2-show-search sprukoweeks"
                                                                data-placeholder="Selecionar dia">
                                                            <option label="Selecionar dia"></option>
                                                            <option value="Lunes" {{$bussiness1 !=null ? $bussiness1->weeks == 'Lunes' ? 'selected': '' :''}}>Lunes</option>
                                                            <option value="Martes" {{$bussiness1 !=null ? $bussiness1->weeks == 'Martes' ? 'selected': '' :''}}>Martes</option>
                                                            <option value="Miercoles" {{$bussiness1 !=null ? $bussiness1->weeks == 'Miercoles' ? 'selected': '' :''}}>Miercoles</option>
                                                            <option value="Jueves" {{$bussiness1 !=null ? $bussiness1->weeks == 'Jueves' ? 'selected': '' :''}}>Jueves</option>
                                                            <option value="Viernes" {{$bussiness1 !=null ? $bussiness1->weeks == 'Viernes' ? 'selected': '' :''}}>Viernes</option>
                                                            <option value="Sabado" {{$bussiness1 !=null ? $bussiness1->weeks == 'Sabado' ? 'selected': '' :''}}>Sabado</option>
                                                            <option value="Domingo" {{$bussiness1 !=null ? $bussiness1->weeks == 'Domingo' ? 'selected': '' :''}}>Domingo</option>
                                                        </select>
                                                    </td>
                                                    <td class="">
                                                        <select name="status1" class="form-control select2 select2-show-search sprukoopen"
                                                                data-placeholder="Seleccionar estado">
                                                            <option label="Seleccionar estado"></option>
                                                            <option value="Abierto" {{$bussiness1 !=null ? $bussiness1->status == 'Abierto' ? 'selected' :'' :''}}>Abierto</option>
                                                            <option value="Cerrado" {{$bussiness1 !=null ? $bussiness1->status == 'Cerrado' ? 'selected' :'' :''}}>Cerrado</option>
                                                        </select>
                                                    </td>
                                                    <td class="tr_clone1">
                                                        <select name="starttime1" class="form-control select2 select2-show-search sprukostarttime" data-placeholder="Hora apertura">
                                                            <option label="Selecciona hora"></option>
                                                            <optgroup>
                                                                <option value="24H" {{$bussiness1 !=null ? $bussiness1->starttime == '24H' ? 'selected' : '' :''}}>24H</option>
                                                            </optgroup>

                                                            @foreach($timestart as $time)
                                                                <option value="{{$time}}" {{ $bussiness1 !=null ? $bussiness1->starttime == $time ? 'selected' : '' :''}}>{{$time}}</option>
                                                            @endforeach

                                                        </select>
                                                    </td>

                                                    <td class="tr_clone">

                                                        <select name="endtime1" class="form-control select2 select2-show-search sprukoendtime" data-placeholder="Hora salida">
                                                            <option label="Seleccionar hora"></option>
                                                            @foreach($timestart as $time)
                                                                <option value="{{$time}}" {{ $bussiness1 !=null ? $bussiness1->endtime == $time ? 'selected' : '' :''}}>{{$time}}</option>
                                                            @endforeach

                                                        </select>
                                                    </td>

                                                </tr>
                                                <tr>
                                                    <td class="tr_weeks">
                                                        <input type="hidden" name="bussinessid2" value="2">
                                                        <input name="bussiness2" class="form-control sprukoweeks" readonly>

                                                    </td>
                                                    <td class="">
                                                        <select name="status2" class="form-control select2 select2-show-search sprukoopen"
                                                                data-placeholder="Seleccionar estado">
                                                            <option label="Seleccionar estado"></option>
                                                            <option value="Abierto" {{$bussiness2 !=null ? $bussiness2->status == 'Abierto' ? 'selected' :'' :''}}>Abierto</option>
                                                            <option value="Cerrado" {{$bussiness2 !=null ? $bussiness2->status == 'Cerrado' ? 'selected' :'' :''}}>Cerrado</option>
                                                        </select>
                                                    </td>
                                                    <td class="tr_clone1">
                                                        <select name="starttime2"
                                                                class="form-control select2 select2-show-search sprukostarttime"
                                                                data-placeholder="Seleccionar apertura">
                                                            <option label="Seleccionar apertura"></option>
                                                            <optgroup>
                                                                <option value="24H" {{$bussiness2 !=null ? $bussiness2->starttime == '24H' ? 'selected' : '' :''}}>24H</option>

                                                            </optgroup>

                                                            @foreach($timestart as $time)
                                                                <option value="{{$time}}" {{ $bussiness2 !=null ? $bussiness2->starttime == $time ? 'selected' : '' :''}}>{{$time}}</option>
                                                            @endforeach

                                                        </select>
                                                    </td>
                                                    <td class="tr_clone">
                                                        <select name="endtime2"
                                                                class="form-control select2 select2-show-search sprukoendtime"
                                                                data-placeholder="Seleccionar cierre">
                                                            <option label="Seleccionar cierre"></option>
                                                            @foreach($timestart as $time)
                                                                <option value="{{$time}}" {{ $bussiness2 !=null ? $bussiness2->endtime == $time ? 'selected' : '' :''}}>{{$time}}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="tr_weeks">
                                                        <input type="hidden" name="bussinessid3" value="3">
                                                        <input name="bussiness3" class="form-control sprukoweeks" readonly>

                                                    </td>
                                                    <td class="">
                                                        <select name="status3" class="form-control select2 select2-show-search sprukoopen"
                                                                data-placeholder="Seleccionar estado">
                                                            <option label="Seleccionar estado"></option>
                                                            <option value="Abierto" {{$bussiness3 !=null ? $bussiness3->status == 'Abierto' ? 'selected' :'' :''}}>Abierto</option>
                                                            <option value="Cerrado" {{$bussiness3 !=null ? $bussiness3->status == 'Cerrado' ? 'selected' :'' :''}}>Cerrado</option>
                                                        </select>
                                                    </td>
                                                    <td class="tr_clone1">
                                                        <select name="starttime3"
                                                                class="form-control select2 select2-show-search sprukostarttime"
                                                                data-placeholder="Seleccionar apertura">
                                                            <option label="Seleccionar apertura"></option>
                                                            <optgroup>
                                                                <option value="24H" {{$bussiness3 !=null ? $bussiness3->starttime == '24H' ? 'selected' : '' :''}}>24H</option>

                                                            </optgroup>
                                                            @foreach($timestart as $time)
                                                                <option value="{{$time}}" {{ $bussiness3 !=null ? $bussiness3->starttime == $time ? 'selected' : '' :''}}>{{$time}}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="tr_clone">
                                                        <select name="endtime3"
                                                                class="form-control select2 select2-show-search sprukoendtime"
                                                                data-placeholder="Seleccionar cierre">
                                                            <option label="Seleccionar cierre"></option>
                                                            @foreach($timestart as $time)
                                                                <option value="{{$time}}" {{ $bussiness3 !=null ? $bussiness3->endtime == $time ? 'selected' : '' :''}}>{{$time}}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="tr_weeks">
                                                        <input type="hidden" name="bussinessid4" value="4">
                                                        <input name="bussiness4" class="form-control sprukoweeks" readonly>

                                                    </td>
                                                    <td class="">
                                                        <select name="status4" class="form-control select2 select2-show-search sprukoopen"
                                                                data-placeholder="Seleccionar estado">
                                                            <option label="Seleccionar estado"></option>
                                                            <option value="Abierto" {{$bussiness4 !=null ? $bussiness4->status == 'Abierto' ? 'selected' :'' :''}}>Abierto</option>
                                                            <option value="Cerrado" {{$bussiness4 !=null ? $bussiness4->status == 'Cerrado' ? 'selected' :'' :''}}>Cerrado</option>
                                                        </select>
                                                    </td>
                                                    <td class="tr_clone1">
                                                        <select name="starttime4"
                                                                class="form-control select2 select2-show-search sprukostarttime"
                                                                data-placeholder="Seleccionar apertura">
                                                            <option label="Seleccionar apertura"></option>
                                                            <optgroup>
                                                                <option value="24H" {{$bussiness4 !=null ? $bussiness4->starttime == '24H' ? 'selected' : '' :''}}>24H</option>

                                                            </optgroup>
                                                            @foreach($timestart as $time)
                                                                <option value="{{$time}}" {{ $bussiness4 !=null ? $bussiness4->starttime == $time ? 'selected' : '' :''}}>{{$time}}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="tr_clone">
                                                        <select name="endtime4"
                                                                class="form-control select2 select2-show-search sprukoendtime"
                                                                data-placeholder="Seleccionar cierre">
                                                            <option label="Seleccionar cierre"></option>
                                                            @foreach($timestart as $time)
                                                                <option value="{{$time}}" {{ $bussiness4 !=null ? $bussiness4->endtime == $time ? 'selected' : '' :''}}>{{$time}}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="tr_weeks">
                                                        <input type="hidden" name="bussinessid5" value="5">
                                                        <input name="bussiness5" class="form-control sprukoweeks" readonly>

                                                    </td>
                                                    <td class="">
                                                        <select name="status5" class="form-control select2 select2-show-search sprukoopen"
                                                                data-placeholder="Seleccionar estado">
                                                            <option label="Seleccionar estado"></option>
                                                            <option value="Abierto" {{$bussiness5 !=null ? $bussiness5->status == 'Abierto' ?
                                            'selected' :'' :''}}>Abierto</option>
                                                            <option value="Cerrado" {{$bussiness5 !=null ? $bussiness5->status == 'Cerrado' ?
                                            'selected' :'' :''}}>Cerrado</option>
                                                        </select>
                                                    </td>
                                                    <td class="tr_clone1">
                                                        <select name="starttime5"
                                                                class="form-control select2 select2-show-search sprukostarttime"
                                                                data-placeholder="Seleccionar apertura">
                                                            <option label="Seleccionar apertura"></option>
                                                            <optgroup>
                                                                <option value="24H" {{$bussiness5 !=null ? $bussiness5->starttime == '24H' ?
                                                'selected' : '' :''}}>24H</option>

                                                            </optgroup>
                                                            @foreach($timestart as $time)
                                                                <option value="{{$time}}" {{ $bussiness5 !=null ? $bussiness5->starttime == $time ? 'selected' : '' :''}}>{{$time}}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="tr_clone">
                                                        <select name="endtime5"
                                                                class="form-control select2 select2-show-search sprukoendtime"
                                                                data-placeholder="Seleccionar cierre">
                                                            <option label="Seleccionar cierre"></option>
                                                            @foreach($timestart as $time)
                                                                <option value="{{$time}}" {{ $bussiness5 !=null ? $bussiness5->endtime == $time ? 'selected' : '' :''}}>{{$time}}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="tr_weeks">
                                                        <input type="hidden" name="bussinessid6" value="6">
                                                        <input name="bussiness6" class="form-control sprukoweeks" readonly>

                                                    </td>
                                                    <td class="">
                                                        <select name="status6" class="form-control select2 select2-show-search sprukoopen"
                                                                data-placeholder="Seleccionar estado">
                                                            <option label="Seleccionar estado"></option>
                                                            <option value="Abierto" {{$bussiness6 !=null ? $bussiness6->status == 'Abierto' ?
                                            'selected' :'' :''}}>Abierto</option>
                                                            <option value="Cerrado" {{$bussiness6 !=null ? $bussiness6->status == 'Cerrado' ?
                                            'selected' :'' :''}}>Cerrado</option>
                                                        </select>
                                                    </td>
                                                    <td class="tr_clone1">
                                                        <select name="starttime6"
                                                                class="form-control select2 select2-show-search sprukostarttime"
                                                                data-placeholder="Seleccionar apertura">
                                                            <option label="Seleccionar apertura"></option>
                                                            <optgroup>
                                                                <option value="24H" {{$bussiness6 !=null ? $bussiness6->starttime == '24H' ?
                                                'selected' : '' :''}}>24H</option>

                                                            </optgroup>
                                                            @foreach($timestart as $time)
                                                                <option value="{{$time}}" {{ $bussiness6 !=null ? $bussiness6->starttime == $time ? 'selected' : '' :''}}>{{$time}}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="tr_clone">
                                                        <select name="endtime6"
                                                                class="form-control select2 select2-show-search sprukoendtime"
                                                                data-placeholder="Seleccionar cierre">
                                                            <option label="Seleccionar cierre"></option>
                                                            @foreach($timestart as $time)
                                                                <option value="{{$time}}" {{ $bussiness6 !=null ? $bussiness6->endtime == $time ? 'selected' : '' :''}}>{{$time}}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="tr_weeks">
                                                        <input type="hidden" name="bussinessid7" value="7">
                                                        <input name="bussiness7" class="form-control sprukoweeks" readonly>

                                                    </td>
                                                    <td class="">
                                                        <select name="status7" class="form-control select2 select2-show-search sprukoopen"
                                                                data-placeholder="Seleccionar estado">
                                                            <option label="Seleccionar estado"></option>
                                                            <option value="Abierto" {{$bussiness7 !=null ? $bussiness7->status == 'Abierto' ?
                                            'selected' :'' :''}}>Abierto</option>
                                                            <option value="Cerrado" {{$bussiness7 !=null ? $bussiness7->status == 'Cerrado' ?
                                            'selected' :'' :''}}>Cerrado</option>
                                                        </select>
                                                    </td>
                                                    <td class="tr_clone1">
                                                        <select name="starttime7"
                                                                class="form-control select2 select2-show-search sprukostarttime"
                                                                data-placeholder="Seleccionar apertura">
                                                            <option label="Seleccionar apertura"></option>
                                                            <optgroup>
                                                                <option value="24H" {{$bussiness7 !=null ? $bussiness7->starttime == '24H' ?
                                                'selected' : '' :''}}>24H</option>

                                                            </optgroup>
                                                            @foreach($timestart as $time)
                                                                <option value="{{$time}}" {{ $bussiness7 !=null ? $bussiness7->starttime == $time ? 'selected' : '' :''}}>{{$time}}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="tr_clone">
                                                        <select name="endtime7"
                                                                class="form-control select2 select2-show-search sprukoendtime"
                                                                data-placeholder="Seleccionar cierre">
                                                            <option label="Seleccionar cierre"></option>
                                                            @foreach($timestart as $time)
                                                                <option value="{{$time}}" {{ $bussiness7 !=null ? $bussiness7->endtime == $time ? 'selected' : '' :''}}>{{$time}}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="col-12">
                            <div class="border-top pt-1 mt-4">
                                <button type="submit" class="btn btn-info  px-4 waves-effect waves-light mt-2 w-100">
                                        Guardar
                                </button>
                            </div>
                        </div>

                        </div>

                    </div>
                </form>
            </div>

        </div>

    </div>

@endsection



@push('scripts')


    <script type="text/javascript">
        "use strict";

        function bussinesshourSubmit(){
            let startStatus = 0,
                endStatus = 0,
                openEle = 0;
            document.querySelectorAll('.sprukoopen').forEach((ele, ind)=>{
                if(ele.value){
                    if(ele.value === "Abierto"){
                        openEle += 1;
                    }
                    let currentEle = ele;
                    if(currentEle.closest('td').nextElementSibling.querySelector('.sprukostarttime').value){
                        startStatus += 1;
                        if(currentEle.closest('td').nextElementSibling.querySelector('.sprukostarttime').value === "24H"){
                            endStatus += 1;
                        }
                    }
                    if(currentEle.closest('td').nextElementSibling.nextElementSibling.querySelector('.sprukoendtime').value){
                        endStatus += 1;
                    }
                }
            })
            if((openEle == startStatus) && (openEle == endStatus)){
                let subBtn = document.querySelector('#bussinesshourSubmit');
                subBtn.disabled = false;
            }
            else{
                let subBtn = document.querySelector('#bussinesshourSubmit');
                subBtn.disabled = true;
            }
        }
        bussinesshourSubmit()


        let dayListEle = document.querySelectorAll('.sprukoweeks');
        let dayListArr = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
        $('.sprukoweeks').on('change', daySort);
        function daySort(){
            let startDay =  dayListEle[0].value;
            if(startDay){
                for(let i = 0; i<= dayListArr.length; i++){
                    if(dayListArr[i] === startDay){
                        let newDayList = reorder(dayListArr, i);
                        dayListEle.forEach((element, ind) => {
                            if(ind >= 1){
                                element.value = newDayList[ind]
                            }
                        });

                    }
                }
                $('.sprukoopen').val('Abierto').trigger('change.select2');
                $('.sprukostarttime').val('0').trigger('change.select2');
                $('.sprukostarttime').prop('disabled', false);
                $('.sprukoendtime').val('0').trigger('change.select2');
                $('.sprukoendtime').prop('disabled', false);
            }
            bussinesshourSubmit();
        }


        function reorder(data, index) {
            return data.slice(index).concat(data.slice(0, index))
        };
        $('.sprukostarttime').on('change', function(e){

            let value = e.target.value,
                tdfind = $(this).closest('tr').find('.tr_clone');

            let find = tdfind[0];
            let selectEle = find.firstElementChild;
            if(value == '24H'){
                $(this).closest('tr').find('.tr_clone select').val('').trigger('change');
                selectEle.disabled = true;
            }else{
                selectEle.disabled = false;
            }

            bussinesshourSubmit();
        });
        $('.sprukoendtime').on('change', function(e){
            bussinesshourSubmit();
        });

        $('.sprukoopen').on('change', function(e){

            let sprukovalue = e.target.value,
                tdfind1 = $(this).closest('tr').find('.tr_clone1'),
                tdfind2 = $(this).closest('tr').find('.tr_clone');

            let find1 = tdfind1[0];
            let selectEle1 = find1.firstElementChild;
            let find2 = tdfind2[0];
            let selectEle2 = find2.firstElementChild;

            if(sprukovalue == 'Cerrado'){
                $(this).closest('tr').find('.tr_clone select').val('').trigger('change');
                $(this).closest('tr').find('.tr_clone1 select').val('').trigger('change');
                selectEle1.disabled = true;
                selectEle2.disabled = true;

                selectEle2.value = null;
            }else{

                selectEle1.disabled = false;
                selectEle2.disabled = false;
            }
            bussinesshourSubmit();
        });

        $(window).on('load', function(){
            let startDay =  dayListEle[0].value;
            if(startDay){
                for(let i = 0; i<= dayListArr.length; i++){
                    if(dayListArr[i] === startDay){
                        let newDayList = reorder(dayListArr, i);
                        dayListEle.forEach((element, ind) => {
                            if(ind >= 1){
                                element.value = newDayList[ind]
                            }
                        });

                    }
                }
            }
            let starttimevalue = $('.sprukostarttime');

            $.map(starttimevalue, function( val, i ) {
                // Do something
                let value = $(val).val(),
                    tdfind = $(val).closest('tr').find('.tr_clone');
                let find = tdfind[0];
                let selectEle = find.firstElementChild;
                if(value == '24H'){

                    selectEle.disabled = true;
                }
            });

            let sprukoopen = $('.sprukoopen');
            $.map(sprukoopen, function( value, i ) {
                // Do something
                let val = $(value).val(),
                    tdfind1 = $(value).closest('tr').find('.tr_clone1'),
                    tdfind2 = $(value).closest('tr').find('.tr_clone');

                let find1 = tdfind1[0];
                let selectEle1 = find1.firstElementChild;
                let find2 = tdfind2[0];
                let selectEle2 = find2.firstElementChild;

                if(val == 'Cerrado'){

                    selectEle1.disabled = true;
                    selectEle2.disabled = true;
                }
            });

            
            $('body').on('click', '#bussinesshourRest', function(){
                $('.sprukoweeks').html('')
                dayListEle.forEach( e => {e.value = ''})
                $('.sprukoopen').html('')
                $('.sprukostarttime').html('')
                $('.sprukoendtime').html('')
            })
        });

    </script>


    <script type="text/javascript">
        Dropzone.autoDiscover = false;

        $(document).ready(function() {


            $("#formHours").validate({
                submit: false,
                ignore: ".ignore",
                rules: {
                    hourstitle: {
                        required: true,
                        minlength: 1,
                        maxlength: 200,
                    },
                    hourssubtitle: {
                        required: true,
                        minlength: 1,
                        maxlength: 200,
                    },
                },
                messages: {
                    hourstitle: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 200 caracter",
                    },
                    hourssubtitle: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 1 caracter",
                        maxlength: "Debe contener al menos 200 caracter",
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formHours');
                    var formData = new FormData($form[0]);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('manager.settings.hours.update') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(response) {

                            if(response.success == true){

                                message = response.message;

                                toastr.success(message, "Operación exitosa", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                setTimeout(function() {
                                    window.location.href = "{{ route('manager.dashboard') }}";
                                }, 2000);

                            }else{

                                $submitButton.prop('disabled', false);
                                error = response.message;

                                toastr.warning(error, "Operación fallida", {
                                    closeButton: true,
                                    progressBar: true,
                                    positionClass: "toast-bottom-right"
                                });

                                $('.errors').text(error);
                                $('.errors').removeClass('d-none');

                            }

                        }
                    });

                }

            });



        });

    </script>


@endpush



