<script>
    const TDOrderNumber = {$pixel.TDOrderNumber|json_encode nofilter}.replace(/#/g, '');
    const TDOrderValue = {$pixel.TDOrderValue|json_encode nofilter};
    const TDCurrency = {$pixel.TDCurrency|json_encode nofilter};
    const TDVoucher = '';
    const TDEvent = {$pixel.eventID|json_encode nofilter};

    (function(i,s,o,g,r,a,m){
        i['TDConversionObject']=r;
        i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)};
        i[r].l=1*new Date();
        a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];
        a.async=1;
        a.src=g;
        m.parentNode.insertBefore(a,m)
    })(window,document,'script', 'https://svht.tradedoubler.com/tr_sdk.js', 'tdconv');

    tdconv('init', '188778', {'element': 'iframe'});
    tdconv('track', 'sale', {
        transactionId: TDOrderNumber,
        ordervalue: TDOrderValue,
        currency: TDCurrency,
        event: TDEvent,
        voucher: TDVoucher
    });

    const getCookie = (name) => {
        let value = `; ${document.cookie}`;
        let parts = value.split(`; ${name}=`);
        if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
        return '';
    };

    const tduid = getCookie('tduid');
    const img = document.createElement('img');
    img.src = `https://img-statics.com/report?o=188778&e=${TDEvent}&ordnum=${TDOrderNumber}&ordval=${TDOrderValue}&curr=${TDCurrency}&tduid=${tduid}&voucher=${TDVoucher}`;
    img.style.display = 'none';
    document.body.appendChild(img);

    const TDAsync = document.createElement('script');
    TDAsync.src = "//swrap.tradedoubler.com/wrap?id=583";
    TDAsync.async = true;
    document.body.appendChild(TDAsync);
</script>
