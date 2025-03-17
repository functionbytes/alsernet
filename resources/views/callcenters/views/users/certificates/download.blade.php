<html>

<head>
    <style>
        @page {
            margin: 0;
            padding: 0;
            border: 0;
            outline: 0;
        }

        @font-face {
        font-family: 'Montserrat';
        src: url({{storage_path('fonts/Montserrat.ttf')}}) format("truetype");
        }

        body {
            font-family: 'Montserrat';
            color: 303030;
        }

        .image-block {
            padding-top: 0px;
            padding-bottom: 0px;
            padding-left: 0px;
            padding-right: 0px;
            margin-top: 0px;
            margin-bottom: 0px;
            margin-left: 0px;
            margin-right: 0px;
        }

        .text-time {
            color: #1a1a16;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 0px;
            padding-bottom: 0px;
            line-height: 0px;
            text-transform: uppercase;
        }

        .text-certifier {
            color: #1a1a16;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 0px;
            text-transform: uppercase;
            padding-bottom: 0px;
            line-height: 0px;
        }

        .text-certifier-description {
            color: #575756;
            font-size: 10px;
            font-weight: 400;
            margin-bottom: 0px;
            padding-bottom: 0px;
            line-height: 12px;
        }

        .text-user {
            color: #1a1a16;
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 0px;
            text-transform: uppercase;
            padding-bottom: 0px;
            line-height: 0px;
        }

        .text-identification {
            color: #1a1a16;
            font-size: 20px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0px;
            padding-bottom: 0px;
            line-height: 0px;
        }

        .text-course {
            color: #1a1a16;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 0px;
            padding-bottom: 0px;
            text-transform: uppercase;
            line-height: 0px;
        }

        .text-end {
            color: #1a1a16;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 0px;
            padding-bottom: 0px;
            line-height: 0px;
            text-transform: uppercase;
        }


        .text-start {
            color: #1a1a16;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 0px;
            padding-bottom: 0px;
            line-height: 0px;
            text-transform: uppercase;
        }


        .contenedor {
            position: relative;
            display: inline-block;
            text-align: center;
        }

        img {
            display: block;
            margin: auto;
        }

        .certifier {
            text-align: center;
            position: absolute;
            top: 87.7%;
            left: 66%;
            right: 0;
            bottom: 0;
            margin: auto;
        }

        .certifier-description {
            text-align: center;
            position: absolute;
            top: 88.2%;
            left: 66%;
            right: 0;
            bottom: 0;
            margin: auto;
        }

        .signature {
            text-align: center;
            position: absolute;
            top: 78.7%;
            left: 80%;
            width: 100px;
            right: 0;
            bottom: 0;
            margin: auto;
        }

        .user {
            text-align: center;
            position: absolute;
            top: 41%;
            left: 0;
            right: 0;
            bottom: 0;
            margin: auto;
        }

        .identification {
            text-align: center;
            position: absolute;
            top: 47.9%;
            left: 51.08%;
        }

        .start {
            text-align: center;
            position: absolute;
            top: 66.8%;
            left: 40.08%;
        }

        .end {
            text-align: center;
            position: absolute;
            top: 66.8%;
            left: 69%;
        }

        .time {
            text-align: center;
            position: absolute;
            top: 61.1%;
            left: 59%;
        }

        .course {
            text-align: center;
            position: absolute;
            top: 57.0%;
            left: 0;
            right: 0;
            bottom: 0;
            margin: auto;
        }
    </style>
</head>

<body>


    @php


    $start = certificate_date($certificate->start_at);
    $end = certificate_date($certificate->end_at);;

    $user = $certificate->user;
    $course = $certificate->course;
    $certification = $certificate->certification;
    $certifier = $certificate->certifier;

    $thumbnail = $certification?->getfirstMedia('thumbnail');
    $signatures = $certifier?->getfirstMedia('signature');

    $image = "/media/" . $thumbnail->id . "/" . $thumbnail->file_name;
    $signature = "/media/" . $signatures->id . "/" . $signatures->file_name;

    @endphp

    <div class="contenedor" style="">

        <img src="{{  public_path() .$image }}" style="height: 792px; width: 1122px;" />

        <div class="user">
            <p class="text-user">{{ $user['firstname'] }} {{ $user['lastname'] }}</p>
        </div>

        <div class="identification">
            <p class="text-identification">{{ $user['identification'] }}</p>
        </div>


        <div class="course">
            <p class="text-course">{{ $course['title'] }}</p>
        </div>

        <div class="time">
            @if($course['duration'] == 1){
            <p class="text-time">{{ $course['duration'] }} hora.</p>
            @else
            <p class="text-time">{{ $course['duration'] }} horas.</p>
            @endif
        </div>

        <div class="start">
            <p class="text-start">{{ $start }}</p>
        </div>

        <div class="end">
            <p class="text-end">{{ $end }}</p>
        </div>

        <img class="signature" src="{{ public_path() .$signature }}" />

        <div class="certifier">
            <p class="text-certifier">{{ $certifier['firstname'] }} {{ $certifier['lastname'] }}</p>
        </div>
        <div class="certifier-description">
            <p class="text-certifier-description">{!! $certifier['description'] !!}</p>
        </div>


    </div>



</body>

</html>