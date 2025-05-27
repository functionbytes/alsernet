

@push('scripts')

<script type="text/javascript">
    $(document).ready(function() {

        $('#btnsprukodisable').attr('disabled', true);
        
        $('.sprukostatuschange').on('click', function(e){
        $(e.target).prop("checked", false)
        if(this){
        $(this).prop("checked", true);
        }else{
        $(this).prop("checked", false);
        }
        if(e.target.value == 'On-Hold')
        {
        
        
        let teatareasecond = $('#holdremove textarea').val();
        if(teatareasecond == '')
        {
        $('#btnsprukodisable').attr('disabled', true);
        }else{
        $('#btnsprukodisable').attr('disabled', false);
        }
        }else{
        if($('#summernoteempty').val() == '')
        {
        $('#btnsprukodisable').attr('disabled', true);
        }else{
        $('#btnsprukodisable').attr('disabled', false);
        }
        }
        });
        
        });

</script>


@endpush